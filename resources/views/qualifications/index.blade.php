@extends('layouts.app')

@section('page-title', 'Qualifications')

@section('content')
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header">Add Qualification</div>
            <div class="card-body">
                <form method="POST" action="{{ route('qualifications.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="qualification-name" class="form-label">Name</label>
                        <input id="qualification-name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" maxlength="120" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button class="btn btn-primary">Add</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header">Available Qualifications</div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($qualifications as $qualification)
                        <tr>
                            <td>{{ $qualification->name }}</td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('qualifications.destroy', $qualification) }}" onsubmit="return confirm('Remove this qualification?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="text-center text-muted py-4">No qualifications added yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
