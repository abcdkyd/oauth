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

    <div id="app">
        <prepare-authorize></prepare-authorize>
    </div>
    <script src="{{ asset('js/app.js') }}"></script>
    <script>
        setTimeout(function() {
            let dataJson = {
                type : 'accountManage'
            }
            let str = JSON.stringify(dataJson)
            window.postMessage(str);
        }, 600)
    </script>
</body>
</html>
