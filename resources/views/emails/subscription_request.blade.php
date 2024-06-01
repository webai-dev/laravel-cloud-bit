<h2>Subscription Request</h2>
<h3>The team {{$request->team->name}} requested a custom plan with the following details:</h3>
<ul>
    <li>
        First name: {{$request->name}}
    </li>
    <li>
        Last name: {{$request->surname}}
    </li>
    <li>
        Email: {{$request->email}}
    </li>
    <li>
        Company name: {{$request->company}}
    </li>
    <li>
        Company Size: {{$request->company_size}}
    </li>
    <li>
        Storage Requirements: {{\App\Util\FileUtils::getHumanSize($request->required_storage * 8)}}
    </li>
    <li>
        Custom Bits: {{$request->custom_bits ? 'Yes' : 'No'}}
    </li>
    <li>
        S3 Integration: {{$request->s3_integration ? 'Yes' : 'No'}}
    </li>
</ul>