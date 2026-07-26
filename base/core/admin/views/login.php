<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>ForPrint Admin</title>

    <style>
        html, body{
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
        }
        body{
            display: flex;
            justify-content: center;
            align-items: center;
        }
        div{
            flex-basis: 500px;
            padding: 15px;
        }
        form{
            display: block;
        }
        label, input{
            display: block;
            margin: auto;
        }
        label, h1{
            text-align: center;
        }
        input{
            margin-bottom: 15px;
            padding: 3px 5px;
        }
        input[type=submit]{
            background: #fff0b7;
            padding: 5px 10px;
            border: 1px solid lemonchiffon;
        }
    </style>
<meta name="robots" content="noindex,nofollow,noarchive">
<link rel="stylesheet" href="<?=PATH . ADMIN_TEMPLATE?>css/forprint-admin-login.css?v=20260725-1615">
</head>
<body>

<div>

    <?php if (!empty($_SESSION['res']['answer'])){
        echo '<p style="color: red; text-align: center">' . $_SESSION['res']['answer'] . '</p>';
        unset($_SESSION['res']);
    }?>

    <h1>ForPrint Admin</h1>

    <form
        action="<?=PATH . $adminPath?>/login" method="post">
        <label for="login">Username</label>
        <input type="text" name="login" id="login">
        <label for="password">Password</label>
        <input type="password" name="password" id="password">
        <input type="submit" value="Sign in">
    </form>
</div>

<script src="<?=PATH . ADMIN_TEMPLATE?>js/frameworkfunctions.js"></script>

<script>

    let form = document.querySelector('form');

    if (form){
        form.addEventListener('submit', e => {
            if (e.isTrusted){
                e.preventDefault();

                Ajax({data:{ajax:'token'}}).then(res => {
                    if (res){
                        form.insertAdjacentHTML('beforeend',`<input type="hidden" name="token" value="${res}">`)
                    }
                    form.submit()
                })
            }
        })
    }

</script>

</body>
</html>
