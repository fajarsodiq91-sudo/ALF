<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Thank You | PT Alfajar Logic Futura</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/icons/alf.png') }}" />
    <style>
      body {
        margin: 0;
        font-family: Inter, Arial, sans-serif;
        background: linear-gradient(135deg, #990000 0%, #b30000 100%);
        color: white;
        min-height: 100vh;
        display: grid;
        place-items: center;
        text-align: center;
        padding: 2rem;
      }
      .card {
        max-width: 560px;
        background: rgba(255,255,255,0.12);
        border: 1px solid rgba(255,255,255,0.2);
        border-radius: 24px;
        padding: 2.2rem;
        box-shadow: 0 20px 50px rgba(0,0,0,0.16);
      }
      h1 { font-size: 2rem; margin-bottom: 0.7rem; }
      p { line-height: 1.7; }
      a { color: white; font-weight: 700; }
    </style>
  </head>
  <body>
    <div class="card">
      <h1>Thank You!</h1>
      <p>Your message has been sent successfully. We will get back to you shortly.</p>
      <p><a href="{{ route('home') }}">Back to homepage</a></p>
    </div>
  </body>
</html>
