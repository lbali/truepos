<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3D Secure Yönlendirme</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
        }
        .container {
            text-align: center;
            padding: 2rem;
        }
        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #e0e0e0;
            border-top-color: #333;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 1rem;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        noscript button {
            padding: 12px 24px;
            font-size: 16px;
            background: #333;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="spinner"></div>
        <p>Banka sayfasına yönlendiriliyorsunuz...</p>

        <form id="truepos-3d-form" method="POST" action="{{ $gatewayUrl }}">
            @foreach($parameters as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach
            <noscript>
                <button type="submit">Devam Et</button>
            </noscript>
        </form>
    </div>

    <script>document.getElementById('truepos-3d-form').submit();</script>
</body>
</html>
