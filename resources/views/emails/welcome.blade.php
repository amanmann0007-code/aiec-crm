<h2>Welcome, {{ $customer->name }}</h2>
<p>Your profile has been created successfully.</p>
<ul>
    <li><strong>PID:</strong> {{ $customer->pid }}</li>
    <li><strong>Country:</strong> {{ $customer->country }}</li>
    <li><strong>Visa Type:</strong> {{ $customer->visa_type }}</li>
    <li><strong>Counselor Assigned:</strong> {{ optional($customer->counselor)->name ?? 'TBD' }}</li>
</ul>
