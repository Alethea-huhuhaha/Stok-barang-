<?php
require 'fuction.php';

//cek login doang takut lupa hehehe
if(isset($_POST['login'])){
    $email = $_POST['email'];
    $password = $_POST['password'];
     
    //ini cockin kayak yang di daabse cari ada atau engak itu dataa 
    $cekdatabase = mysqli_query($conn, "SELECT * FROM login where email='$email' and password='$password' ");
    $hitung = mysqli_num_rows($cekdatabase);

    if($hitung>0){
        $_SESSION['log'] = 'True';
        header('location:index.php');
    } else {
        header('location:login.php');
    };
};


if(!isset($_SESSION['log'])){

} else {
    header('location:index.php');
}

?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>Login</title>
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet" />
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet" />
        <style>
            * {
                box-sizing: border-box;
            }

            html,
            body {
                margin: 0;
                min-height: 100%;
            }

            body {
                min-height: 100vh;
                min-height: 100svh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 120px 20px 60px;
                font-family: "Poppins", "Segoe UI", Tahoma, sans-serif;
                color: #fff;
                background-color: #071527;
                background-image:
                    url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='128' height='64' viewBox='0 0 128 64'%3E%3Cg fill='none' stroke='%23040c19' stroke-width='5' stroke-opacity='.85'%3E%3Cpath d='M0 2h128M0 34h128M0 2v32M64 2v32M32 34v30M96 34v30'/%3E%3C/g%3E%3C/svg%3E"),
                    radial-gradient(ellipse 300px 240px at 50% 10%, rgba(255, 224, 160, 0.4), transparent 70%),
                    radial-gradient(ellipse 520px 400px at 50% 12%, rgba(255, 197, 110, 0.55), rgba(255, 180, 80, 0.12) 55%, transparent 72%),
                    radial-gradient(ellipse 1100px 800px at 50% 30%, rgba(30, 76, 150, 0.5), transparent 72%),
                    linear-gradient(180deg, #050d1a 0%, #081527 45%, #0a1b32 100%);
                background-size: 128px 64px, 100% 100%, 100% 100%, 100% 100%, 100% 100%;
                background-attachment: fixed;
                overflow-x: hidden;
            }

            body::after {
                content: "";
                position: fixed;
                inset: 0;
                pointer-events: none;
                background:
                    radial-gradient(ellipse at 50% 120%, rgba(0, 0, 0, 0.55), transparent 60%),
                    linear-gradient(180deg, rgba(0, 0, 0, 0.35), transparent 26%);
                z-index: 0;
            }

            .scene {
                position: relative;
                z-index: 1;
                display: flex;
                flex-direction: column;
                align-items: center;
                width: 100%;
                max-width: 420px;
            }

            /* ---------- Lampu dinding ---------- */
            .lamp {
                position: relative;
                width: 120px;
                height: 34px;
                border-radius: 6px 6px 8px 8px;
                background: linear-gradient(180deg, #0d203a, #06111f);
                box-shadow:
                    0 8px 20px rgba(0, 0, 0, 0.55),
                    inset 0 2px 0 rgba(120, 160, 220, 0.12);
                z-index: 2;
            }

            .lamp::before {
                content: "";
                position: absolute;
                left: -5px;
                right: -5px;
                bottom: -5px;
                height: 11px;
                border-radius: 4px;
                background: linear-gradient(180deg, #122744, #081526);
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.6);
            }

            .lamp::after {
                content: "";
                position: absolute;
                left: 6px;
                right: 6px;
                bottom: -9px;
                height: 8px;
                border-radius: 4px;
                background: linear-gradient(180deg, #ffe9b3, #ffc35c);
                box-shadow:
                    0 0 18px 6px rgba(255, 196, 100, 0.85),
                    0 0 70px 30px rgba(255, 180, 80, 0.3);
            }

            .beam {
                position: absolute;
                top: 30px;
                left: 50%;
                width: min(560px, 140vw);
                height: 430px;
                transform: translateX(-50%);
                background: linear-gradient(180deg, rgba(255, 205, 120, 0.45), rgba(255, 190, 90, 0) 72%);
                clip-path: polygon(40% 0, 60% 0, 100% 100%, 0 100%);
                filter: blur(14px);
                pointer-events: none;
                z-index: 0;
            }

            /* ---------- Kartu kaca ---------- */
            .login-card {
                position: relative;
                z-index: 2;
                width: 100%;
                margin-top: 46px;
                padding: 42px 34px 30px;
                border-radius: 20px;
                background: linear-gradient(160deg, rgba(255, 255, 255, 0.24), rgba(255, 255, 255, 0.08));
                border: 1px solid rgba(255, 255, 255, 0.4);
                box-shadow:
                    0 30px 60px rgba(0, 0, 0, 0.45),
                    inset 0 1px 0 rgba(255, 255, 255, 0.35);
                backdrop-filter: blur(16px) saturate(130%);
                -webkit-backdrop-filter: blur(16px) saturate(130%);
            }

            .login-card h1 {
                margin: 0 0 32px;
                text-align: center;
                font-size: 36px;
                font-weight: 700;
                letter-spacing: 0.5px;
                color: #fff;
                text-shadow: 0 2px 12px rgba(0, 0, 0, 0.35);
            }

            .field {
                position: relative;
                margin-bottom: 18px;
            }

            .field input {
                width: 100%;
                height: 54px;
                padding: 0 52px 0 22px;
                border-radius: 999px;
                border: 1px solid rgba(255, 255, 255, 0.55);
                background: rgba(255, 255, 255, 0.08);
                color: #fff;
                font-family: inherit;
                font-size: 15px;
                outline: none;
                transition: background 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
            }

            .field input::placeholder {
                color: rgba(255, 255, 255, 0.88);
            }

            .field input:focus {
                background: rgba(255, 255, 255, 0.16);
                border-color: #fff;
                box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.15);
            }

            .field input:-webkit-autofill,
            .field input:-webkit-autofill:hover,
            .field input:-webkit-autofill:focus {
                -webkit-text-fill-color: #fff;
                box-shadow: inset 0 0 0 1000px rgba(22, 42, 68, 0.9);
                transition: background-color 5000s ease-in-out 0s;
            }

            .field i {
                position: absolute;
                top: 50%;
                right: 20px;
                transform: translateY(-50%);
                font-size: 16px;
                color: #fff;
                opacity: 0.9;
                pointer-events: none;
            }

            .row-between {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                margin: 2px 2px 24px;
                font-size: 13px;
                font-weight: 500;
                color: #fff;
            }

            .remember {
                display: flex;
                align-items: center;
                gap: 8px;
                cursor: pointer;
                user-select: none;
            }

            .remember input[type="checkbox"] {
                appearance: none;
                -webkit-appearance: none;
                display: grid;
                place-content: center;
                width: 15px;
                height: 15px;
                margin: 0;
                border: 1.5px solid #fff;
                border-radius: 2px;
                background: rgba(255, 255, 255, 0.15);
                cursor: pointer;
                transition: background 0.15s ease;
            }

            .remember input[type="checkbox"]::after {
                content: "";
                width: 8px;
                height: 4px;
                border-left: 2px solid #16233a;
                border-bottom: 2px solid #16233a;
                transform: rotate(-45deg) translateY(-1px);
                opacity: 0;
            }

            .remember input[type="checkbox"]:checked {
                background: #fff;
                border-color: #fff;
            }

            .remember input[type="checkbox"]:checked::after {
                opacity: 1;
            }

            .forgot {
                color: #fff;
                text-decoration: none;
                transition: opacity 0.2s ease;
            }

            .forgot:hover {
                text-decoration: underline;
            }

            .btn-login {
                width: 100%;
                height: 54px;
                border: 0;
                border-radius: 999px;
                background: #fff;
                color: #16233a;
                font-family: inherit;
                font-size: 16px;
                font-weight: 600;
                letter-spacing: 0.3px;
                cursor: pointer;
                transition: transform 0.2s ease, background 0.2s ease, box-shadow 0.2s ease;
            }

            .btn-login:hover {
                background: #eef2f7;
                transform: translateY(-1px);
                box-shadow: 0 10px 24px rgba(0, 0, 0, 0.25);
            }

            .btn-login:active {
                transform: translateY(0);
                box-shadow: none;
            }

            .register {
                margin: 26px 0 0;
                text-align: center;
                font-size: 13px;
                font-weight: 500;
                color: rgba(255, 255, 255, 0.92);
            }

            .register a {
                color: #fff;
                font-weight: 600;
                text-decoration: none;
            }

            .register a:hover {
                text-decoration: underline;
            }

            .sr-only {
                position: absolute;
                width: 1px;
                height: 1px;
                padding: 0;
                margin: -1px;
                overflow: hidden;
                clip: rect(0, 0, 0, 0);
                white-space: nowrap;
                border: 0;
            }

            :focus-visible {
                outline: 2px solid #fff;
                outline-offset: 3px;
            }

            @media (max-width: 480px) {
                body {
                    padding: 100px 24px 48px;
                }

                .login-card {
                    margin-top: 36px;
                    padding: 34px 22px 26px;
                }

                .login-card h1 {
                    margin-bottom: 26px;
                    font-size: 30px;
                }

                .row-between {
                    font-size: 12px;
                }
            }

            @media (prefers-reduced-motion: reduce) {
                * {
                    transition: none !important;
                }
            }
        </style>
    </head>
    <body>
        <div class="scene">
            <div class="lamp" aria-hidden="true"></div>
            <div class="beam" aria-hidden="true"></div>

            <main class="login-card">
                <h1>Login</h1>

                <form method="post" autocomplete="on">
                    <div class="field">
                        <label class="sr-only" for="inputEmailAddress">Email</label>
                        <input
                            class="form-control"
                            name="email"
                            id="inputEmailAddress"
                            type="email"
                            placeholder="Email"
                            autocomplete="email"
                            required
                            autofocus
                        />
                        <i class="fas fa-user" aria-hidden="true"></i>
                    </div>

                    <div class="field">
                        <label class="sr-only" for="inputPassword">Password</label>
                        <input
                            class="form-control"
                            name="password"
                            id="inputPassword"
                            type="password"
                            placeholder="Password"
                            autocomplete="current-password"
                            required
                        />
                        <i class="fas fa-lock" aria-hidden="true"></i>
                    </div>

                    <div class="row-between">
                        <label class="remember">
                            <input type="checkbox" name="remember" value="1" />
                            Remember me
                        </label>
                        <a class="forgot" href="#">Forgot password?</a>
                    </div>

                    <button class="btn-login" type="submit" name="login">Login</button>
                </form>

                <p class="register">Don't have an account? <a href="#">Register</a></p>
            </main>
        </div>
    </body>
</html>
