<?php
    include('connection.php');
?>   
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acessar</title>
    <link rel="shortcut icon" href="img/logo.ico" type="image/x-icon">
    <link rel="stylesheet" href="css/user.css">
    <style>
        .p-login {
            text-align: center;
        }

        :root {
            --color0: #F3F4F0;
            --color1: #CED8D0;
            --color2: #96A5A0;
            --color3: #4F5C55;
            --color4: #517709;
        }

        a {
            text-decoration: none;
            color: white;
        }
    </style>
</head>

<body>
    <section>
        <h1>Acessar</h1>
        <form action="#" method="get" autocomplete="off">
            <hr>
            <br>
            <p class="p-login">
                <label for="id-nome" class="lb1">Nome:</label>
                <input type="text" name="nome" class="inpt1" id="id-nome" placeholder="insira um usuário..." required>
            </p>
            <p class="p-login">
                <label for="id-senha" class="lb2">Senha:</label>
                <input type="password" name="senha" class="inpt2" id="id-senha" placeholder="insira uma senha..."
                    required>
            </p>
            <br>
            <hr>
            <p>
                <input type="submit" value="ENTRAR" class="inpt_btn_reg">
            </p>            
        </form>  
        <p>
            <button class="btn-cadastrar" id="btn-cad">
                <a href="user/register.html" class="link" target="frame">Ainda não sou cadastrado</a>
            </button>
        </p>      
        <dialog>
            <iframe src="user/register.html" name="frame" frameborder="0"></iframe>
            <br>
            <button class="btn-fechar">FECHAR</button>
        </dialog>       
    </section>
</body>
<script src="js/modal-user-log.js"></script>

</html>  

