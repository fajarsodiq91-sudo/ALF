<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700|pt-serif:400,700i|alex-brush&display=swap" rel="stylesheet" />
    <style>
        @page { margin: 0; size: 297mm 210mm; }
        body { margin: 0; }
        .cert-page + .cert-page { page-break-before: always; }
    </style>
</head>
<body>
    @foreach ($certificates as $certificate)
        <div class="cert-page">
            @include('certificates._design', [
                'certificate' => $certificate,
                'logoSrc' => public_path('assets/icons/alf.png'),
                'embedImages' => extension_loaded('gd'),
                'pdf' => true,
            ])
        </div>
    @endforeach
</body>
</html>
