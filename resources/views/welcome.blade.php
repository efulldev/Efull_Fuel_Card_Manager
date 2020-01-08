@extends('layouts.app')

@section('content')

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css?family=Nunito:200,600" rel="stylesheet">

        <!-- Styles -->
        <style>
            html, body {
                background-color: #fff;
                color: #636b6f;
                font-family: 'Nunito', sans-serif;
                font-weight: 200;
                height: 100vh;
                margin: 0;
            }

            .main {
                background-image: url('{{asset("img/bg_car.jpg")}}');
                height: 95vh;
                background-size: cover;   
            }

            .mask{
                background-color: #000000cc;
                height: inherit;
            }
            
        </style>
    </head>
    <body>
        <div class="container-fluid main">
            <div class="row mask">
                <div class="col-md-3">

                </div>
                <div class="col-md-6">
                    <div style="text-align:center; font-size: 4em; margin-top: 40vh;">
                        Efull Fuel Card System Manager
                    </div>
                </div>
                <div class="col-md-3">
                    
                </div>
            </div>
        </div>
    </body>
@endsection