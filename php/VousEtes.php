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
             margin-top: 5px;
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

        /*///////////////////////////////////////////////////////////////////////////////*/

        body{
             background: linear-gradient(#EEE8AA,#F0E68C);
             background-repeat: no-repeat;
             background-attachment: fixed;
        }
        #myH1{
             color :#000;
             font-family: georgia;
             text-align: center;
             font-size: 45px;
             padding-left: 20px;
             /*margin-top: 9%;*/
             letter-spacing: 2px;
        }

        #all{
              /* margin: 100px; */
             margin: 50px auto; /* More flexible margin, centered */
             padding: 20px; /* Add some padding for content spacing */
             max-width: 1200px; /* Consistent max-width */
             width: 90%; 

        }

        .box{
             display: flex;
             justify-content: space-around;
             align-items: center;
              margin-top: 50px; /* Reduced top margin */
             flex-wrap: wrap; /* Allow boxes to wrap */
             gap: 20px; 
        }

        .boite{

              flex: 1 1 200px; /* Flex grow, shrink, basis - allows boxes to grow and shrink */
             max-width: 250px; /* Max width for larger screens */
             min-width: 180px;
             border: solid;
             border-color: #2e916e;
              padding: 40px 20px;
             border-radius:5px;
             background-color: #2e916e;
             text-align: center; 
        }

        .boite a{
             color: #faf5f5;
             text-decoration:none;
             font-family: georgia;
             font-size: 20px;
             text-align: center;
        }

        .boite:hover{
             background-color: #62a78f;
             border-color:#62a78f ;
             box-shadow: 5px 5px 10px #1b3f33 , -5px -5px 10px #1b3f33 ;
        }


/* Media Queries for Responsiveness */
        @media (max-width: 992px) { /* Medium devices (tablets, less than 992px) */
            .menu ul {
                justify-content: flex-start; /* Align items to start when wrapping */
            }
            ul li {
                margin: 10px; /* Adjust margin for smaller screens */
            }
            .boite {
                padding: 30px 15px;
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
            #myH1 {
                font-size: 35px; /* Adjust heading font size */
            }
            .box {
                flex-direction: column; /* Stack .boite elements vertically */
                align-items: center; /* Center them when stacked */
            }
            .boite {
                width: 80%; /* Make boxes take more width when stacked */
                max-width: 300px; /* But not too wide */
                margin-bottom: 20px; /* Add space between stacked boxes */
                padding: 25px 15px;
            }
        }

        @media (max-width: 576px) { /* Extra small devices (portrait phones, less than 576px) */
            #myH1 {
                font-size: 28px;
            }
            .btn {
                width: 100%; /* Make button full width on very small screens */
            }
            .boite {
                width: 90%;
                padding: 20px 10px;
            }
            .boite a {
                font-size: 18px;
            } }


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
                 </ul>
                
            </div>

        </div> 
    </div>

    <div id="all">

        <h1 id="myH1">Vous Etes : </h1>

        <div class="box">
            <div class="boite"><a href="authentificationAdministrateur.php">Administrateur</a></div>
            <div class="boite"><a href="authentificationResponsable.php">Respo PFE</a></div>
            <div class="boite"><a href="authentificationEncadrant.php">Encadrant</a></div>
            <div class="boite"><a href="authentificationEtudiant.php">Etudiant</a></div>

        </div>

    </div>
    
   
    

</body>
</html>