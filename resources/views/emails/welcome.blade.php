<h2>Welcome, {{ $customer->name }}</h2>
<p>Your profile has been created successfully with {{ config('company.name') }}.</p>
<ul>
    <li><strong>PID:</strong> {{ $customer->pid }}</li>
    <li><strong>Duration:</strong> {{ $customer->duration_label }}</li>
    <li><strong>Course Type:</strong> {{ $customer->visa_type }}</li>
    <li><strong>Counselor Assigned:</strong> {{ optional($customer->counselor)->name ?? 'TBD' }}</li>
</ul>
