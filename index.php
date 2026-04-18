<!DOCTYPE html>
<html lang="en">
<head>
         <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des PFE</title>
    
    <style>
        *{
             margin: 0;
             padding: 0;
        }

        body{
             background: linear-gradient(#7dd0b3,rgb(8, 105, 81));
             background-image: url("graduation.webp");
             background-attachment: fixed;
             background-position:center;
             background-size:cover;
        }


        .navbar{
              /* width: 1200px; */
             max-width: 1200px;
             width: 90%; /* Make it fluid */
             min-height: 75px; /* Use min-height */
             margin: auto;
             display: flex; /* Use flexbox for layout */
             justify-content: space-between; /* Space between logo and menu */
             align-items: center; /* Vertically align items */
             flex-wrap: wrap; /* Allow wrapping for smaller screens */

        } 

        .logo{
             color:#2e916e ;
             font-size: 35px;
             font-family:Georgia ;
             padding-left: 0px;
             
             padding-top: 20px;
             margin-top: 5px
        }

        .menu{
              display: flex; /* To align the ul properly if needed */
             align-items: center;
        }

        ul{
            
             display: flex;
             
             align-items: center;
             list-style: none; /* Moved from li to ul for consistency */
             padding: 0; /* Remove default ul padding */
             flex-wrap: wrap; 
        }

        ul li{
              margin: 10px 15px;
             font-size: 14px;
        }

        ul li a{
             text-decoration:none;
             color: #fff;
             font-family: Arial;
             font-weight:bold;
             transition: 0.4s ease-in-out;
        }

        ul li a:hover{
             color: #2e916e;
        }

        .contact{
             border:#2e916e;
             width: 330px;
             
             margin-left: 270px;
        }

        .btn{
             width: 200px;
             height: 40px;
             background: #2e916e;
             border: 2px solid #2e916e;
             margin-top: 5px;
             color: #fff;
             font-size: 15px;
             border-radius: 10px;
             transition: 0.2s ease;
             cursor:pointer;
        }

        .btn:hover{
             color: #000;
        }

        .btn:focus{
             outline: none;
        }

        .content{
             max-width: 1200px;
             width: 90%;  
             height: auto;
             margin: 20px auto;
             color:#2e916e;
             position: relative;
        }

        .content .par{
             padding-left: 20px;
             padding-bottom: 25px;
             font-family: Arial;
             /*letter-spacing: 1.2px;*/
             line-height: 30px;
        }

        .content h1{
             font-family: 'Times New Roman';
             font-size: 65px;
             padding-left: 20px;
             margin-top: 9%;
             letter-spacing: 2px;
             color:#2e916e;
        }


        .content span{
             color: #2e916e;
             font-size: 65px;
        }



 /* Media Queries for Responsiveness */
        @media (max-width: 992px) { /* Medium devices (tablets, less than 992px) */
            .menu ul {
                justify-content: flex-start; /* Align items to start when wrapping */
            }
            ul li {
                margin: 10px; /* Adjust margin for smaller screens */
            }
        }

        @media (max-width: 768px) { /* Small devices (landscape phones, 768px and down) */
            .navbar {
                flex-direction: column; /* Stack logo and menu */
                align-items: flex-start; /* Align items to the start */
            }
            .menu {
                width: 100%; /* Menu takes full width */
                margin-top: 15px;
            }
            .menu ul {
                flex-direction: column; /* Stack menu items */
                align-items: flex-start; /* Align items to the start */
                width: 100%;
            }
            .menu ul li {
                margin: 10px 0; /* Adjust margin for stacked items */
                width: 100%;
            }
            .menu ul li a, .menu ul li .btn { /* Target button specifically if it's directly in li */
                display: block;
                width: 100%;
                box-sizing: border-box; /* Include padding and border in the element's total width and height */
                text-align: left;
            }
            .content h1, .content span {
                font-size: 45px; /* Adjust font size for smaller screens */
            }
            .content .par {
                font-size: 1em; /* Adjust paragraph font size */
                line-height: 1.6; /* Improve readability */
            }
        }

        @media (max-width: 576px) { /* Extra small devices (portrait phones, less than 576px) */
            .content h1, .content span {
                font-size: 35px;
            }
            .btn {
                width: 100%; /* Make button full width on very small screens */
            }
        }





    </style>
</head>
<body>

    <div class="main">
        <div class="navbar">
            <div class="icon">
                <h2 class="logo">PFE Gestion</h2>
            </div>

            <div class="menu">
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="Apropos.php">à propos</a></li>
                    <li><a href="VousEtes.php">Vous etes</a></li>
                    <li><a href="AuthentificationAdministrateur.php">Administrateur</a></li>
                    <li><a href="AuthentificationResponsable.php">Respo PFE</a></li>
                    <li><a href="AuthentificationEncadrant.php">Encadrant</a></li>
                    <li><a href="authentificationEtudiant.php">Etudiant</a></li>
                    <li><a href="mailto:kadirkadij50@gmail.com?subject=Question ou probleme %20sur%20l'%20application &body=Bonjour,%0A%0AJ'ai%20une%20question ou probleme%20concernant..."><button class="btn" onclick="takeToContactPage()">Contactez-nous</button></a></li>                </ul>
                
            </div>

        </div> 
        <div class="content">
            <h1>  Bienvenue dans<br><span>L'application Web pour</span> <br>La Gestion des PFE</h1> <br>
            <h2 class="par">L'application va vous offre des services agréables que vous soyez des encadrants, des étudiants, des responsable de PFE ou meme des administrateurs.<br>Soyez les bienvenus. 
                <br></h2>
        </div>
    </div>
    <script src="https://unpkg.com/ionicons@5.4.0/dist/ionicons.js"></script>
</body>
</html>