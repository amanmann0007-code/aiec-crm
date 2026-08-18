<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Notification;
use App\Models\Remark;
use App\Models\RemarkTag;
use App\Models\User;

class RemarkTagService
{
    public static function process(Remark $remark, Customer $customer, string $message, array $taggedUserIds, $actor): void
    {
        $ids = collect($taggedUserIds)->filter()->unique()->values();
        $allowedAgentIds = null;

        if ($actor && $actor->role === 'agent') {
            $allowedAgentIds = collect([$customer->agent_id])
                ->merge($customer->collaborators()->where('users.status', 'active')->pluck('users.id'))
                ->filter()
                ->unique()
                ->values();
            $ids = $ids->intersect($allowedAgentIds);
        }

        preg_match_all('/@([a-zA-Z0-9][a-zA-Z0-9._-]*)/', $message, $matches);
        $handles = collect($matches[1] ?? [])
            ->map(fn ($handle) => strtolower($handle))
            ->filter()
            ->unique()
            ->values();

        if ($handles->isNotEmpty()) {
            $users = User::where('status', 'active')
                ->where(function ($query) use ($handles) {
                    foreach ($handles as $handle) {
                        $nameProbe = str_replace(['.', '_', '-'], ' ', $handle);

                        $query->orWhere('email', 'like', "{$handle}@%")
                            ->orWhere('email', 'like', "{$handle}.%")
                            ->orWhere('email', 'like', "{$handle}-%")
                            ->orWhere('email', 'like', "{$handle}_%")
                            ->orWhere('name', 'like', "{$handle}%")
                            ->orWhere('name', 'like', "{$nameProbe}%");
                    }
                })
                ->get(['id', 'name', 'email']);

            foreach ($handles as $handle) {
                $matchedUser = $users->first(fn (User $user) => in_array($handle, $user->mentionAliases(), true));

                if ($matchedUser && ($allowedAgentIds === null || $allowedAgentIds->contains($matchedUser->id))) {
                    $ids->push($matchedUser->id);
                }
            }
        }

        $taggedUsers = User::whereIn('id', $ids->unique()->values())
            ->where('id', '!=', $actor->id)
            ->when($allowedAgentIds !== null, function ($query) use ($allowedAgentIds) {
                $query->where('role', 'agent')
                    ->where('status', 'active')
                    ->whereIn('id', $allowedAgentIds);
            })
            ->get(['id', 'name']);

        foreach ($taggedUsers as $taggedUser) {
            RemarkTag::firstOrCreate([
                'remark_id' => $remark->id,
                'tagged_user_id' => $taggedUser->id,
            ]);

            $summary = $customer->activitySummary();

            Notification::create([
                'user_id' => $taggedUser->id,
                'title' => 'You were tagged in a remark',
                'message' => "{$actor->name} tagged you on {$summary}",
                'customer_id' => $customer->id,
            ]);

        }
    }
}
