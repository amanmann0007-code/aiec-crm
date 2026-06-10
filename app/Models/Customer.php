<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'pid',
        'name',
        'phone',
        'email',
        'dob',
        'gender',
        'marital_status',
        'father_spouse_name',
        'residence_country',
        'qualification',
        'qualification_year',
        'gap_years',
        'english_exam',
        'score',
        'visa_type',
        'intake_month',
        'intake_year',
        'country',
        'source',
        'reference_name',
        'telecaller_id',
        'english_test',
        'test_type',
        'listening',
        'reading',
        'writing',
        'speaking',
        'overall',
        'test_expiry',
        'previous_refusal',
        'assigned_counselor_id',
        'status',
        'visit_date',
        'created_by',
    ];

    protected $casts = [
        'visit_date' => 'date',
    ];

    public function activitySummary(): string
    {
        $visa = $this->visa_type ?: 'N/A';
        $country = $this->country
            ? (config('crm.countries')[$this->country] ?? $this->country)
            : 'N/A';

        return "{$this->name} ({$visa}) {$country} - {$this->pid}";
    }

    public function counselor()
    {
        return $this->belongsTo(User::class, 'assigned_counselor_id');
    }

    public function telecaller()
    {
        return $this->belongsTo(User::class, 'telecaller_id');
    }

    public function remarks()
    {
        return $this->hasMany(Remark::class);
    }

    public function followUps()
    {
        return $this->hasMany(FollowUp::class);
    }

    public function nextFollowUp()
    {
        return $this->hasOne(FollowUp::class)
            ->where('status', 'pending')
            ->whereNotNull('follow_up_date')
            ->orderBy('follow_up_date');
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function fees()
    {
        return $this->hasMany(CustomerFee::class);
    }

    public function feeReceiptLogs()
    {
        return $this->hasMany(FeeReceiptLog::class);
    }

    public function refusals()
    {
        return $this->hasMany(CustomerRefusal::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function processSteps()
    {
        return $this->hasMany(CustomerProcessStep::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
