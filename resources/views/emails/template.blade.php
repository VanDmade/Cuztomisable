<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Cuztomisable')</title>
    <style>
        :root {
            --color-primary: #FF6F61;
            --color-secondary: #30D5C8;
            --color-accent: #FFEB99;
            --color-danger: #FF4B4B;
            --color-success: #6AB26A;
            --color-info: #87CEFA;
            --color-warning: #FFA500;
        }
        body {
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            font-family: Arial, sans-serif;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 30px auto;
            background-color: #ffffff;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .header {
            background-color: var(--color-primary);
            color: #fff;
            text-align: center;
            padding: 20px;
        }
        .header img {
            max-height: 40px;
            margin-bottom: 10px;
        }
        .content {
            padding: 24px;
            line-height: 1.6;
        }
        .highlight {
            background-color: var(--color-accent);
            padding: 16px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .button {
            display: inline-block;
            background-color: var(--color-secondary);
            color: #fff;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 4px;
            font-weight: bold;
            margin-top: 20px;
        }
        .footer {
            background-color: #f0f0f0;
            padding: 14px 20px;
            font-size: 12px;
            text-align: center;
            color: #555;
        }
        .footer a {
            color: var(--color-primary);
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ $logo ?? 'https://via.placeholder.com/140x40?text=Cuztomisable' }}" alt="{{ $company ?? 'Cuztomisable' }} Logo">
            <h1>@yield('header', 'Cuztomisable')</h1>
        </div>
        <div class="content">
            @yield('content')
        </div>
        <div class="footer">
            <p>You're receiving this email because you're a registered user of {{ $company ?? 'Cuztomisable' }}.</p>
            @if (isset($unsubscribe))
                <p><a href="{{ $unsubscribe ?? '#' }}">Unsubscribe</a>
                @if (isset($support))
                    | <a href="{{ $support ?? '#' }}">Contact Support</a></p>
                @endif
            @endif
        </div>
    </div>
</body>
</html>
