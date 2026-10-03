<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $certificate->number }}</title>
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700|pt-serif:400,700i|alex-brush&display=swap" rel="stylesheet" />
    <style>
        @page { margin: 0; size: 297mm 210mm; }
        body { margin: 0; }
    </style>
</head>
<body>
    @include('certificates._design', [
        'certificate' => $certificate,
        'logoSrc' => public_path('assets/icons/alf.png'),
        // The GD extension embeds PNG/JPG images into the PDF; without it, the design falls back to text only.
        'embedImages' => extension_loaded('gd'),
        'pdf' => true,
    ])
</body>
</html>
