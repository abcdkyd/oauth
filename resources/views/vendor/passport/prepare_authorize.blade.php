<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>用户授权</title>

</head>
<body>
    <script src="{{ asset('js/app.js') }}"></script>
    <div id="app">
        <prepare-authorize></prepare-authorize>
    </div>
</body>
</html>
