<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>JF Cuts</title>
    <style>
        :root { --bg:#F7F5F2; --card:#fff; --text:#181818; --accent:#8B5E3C; --border:#E7E2DC; }
        * { box-sizing: border-box; }
        body { margin:0; background:var(--bg); color:var(--text);
            font-family: system-ui, -apple-system, sans-serif; }
        .wrap { max-width: 430px; margin: 0 auto; padding: 24px 16px; }
        .card { background:var(--card); border:1px solid var(--border);
            border-radius:16px; padding:24px; }
        h1 { font-size:28px; margin:0 0 20px; }
        label { display:block; font-size:14px; margin:16px 0 6px; }
        input { width:100%; padding:14px; font-size:16px; border:1px solid var(--border);
            border-radius:12px; background:#fff; }
        button, .btn { display:block; width:100%; margin-top:24px; padding:16px; font-size:16px;
            border:0; border-radius:12px; background:var(--accent); color:#fff;
            text-align:center; text-decoration:none; cursor:pointer; }
        .link { text-align:center; margin-top:20px; font-size:14px; }
        .link a { color:var(--accent); }
        .error { color:#b3261e; font-size:13px; margin-top:6px; }
    </style>
</head>
<body>
<div class="wrap">@yield('content')</div>
</body>
</html>
