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

        body{
             background:linear-gradient( #EEE8AA,#F0E68C);
             background-repeat:no-repeat;
             background-attachment:fixed;
        }


        #myH1{
             font-family: Georgia, Times, 'Times New Roman', serif;
             color:#2e916e;
    
        }

        #myPs{
             font-family: Helvetica;
             font-size:medium;
             margin-top: 50px;

        }

        img{
             border-radius: 5px;
             max-width: 100%; /* Make image responsive */
             height: auto;   /* Maintain aspect ratio */
             width: 45%; 
             margin-left: 3%;
             float: right;
        }

       #all{
              max-width: 1200px; /* Consistent max-width */
             width: 90%;       /* Fluid width */
             margin: 50px auto; /* Centered with top/bottom margin */
             padding: 20px;    /* Add some padding */
             overflow: hidden; 
        }


       #ProposH1{
             font-family: 'Times New Roman';
             font-size: 20px;
             padding-left: 0px;
             margin-top: 9%;
             letter-spacing: 2px;
             color:#2e916e;
        }


 /* Clearfix for #all to contain floated image */
        #all::after {
            content: "";
            clear: both;
            display: table;
        }

        /* Media Queries for Responsiveness */
        @media (max-width: 992px) { /* Medium devices (tablets, less than 992px) */
            .menu ul {
                justify-content: flex-start; /* Align items to start when wrapping */
            }
            ul li {
                margin: 10px; /* Adjust margin for smaller screens */
            }
            img {
                width: 100%; /* Image takes full width of its column */
                float: none; /* Remove float */
                margin: 20px auto; /* Center image */
                display: block;
            }
            #myPs { /* Ensure text container takes full width when image is not floated */
                width: 100%;
                margin-top: 20px;
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
                box-sizing: border-box;
                text-align: left;
            }
            #myH1, #ProposH1 { font-size: 1.5em; } /* Adjust heading font sizes */
            #myPs p { font-size: 0.95em; line-height: 1.6; } /* Adjust paragraph font size and line height */
        }

        @media (max-width: 576px) { /* Extra small devices (portrait phones, less than 576px) */
            #myH1, #ProposH1 { font-size: 1.3em; }
            .btn { width: 100%; } /* Make button full width */
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
                 </ul>
                
            </div>

        </div> 
    </div>
    <div id="container">
        <div id="all">
    
            <h1 id="ProposH1">A propos des </h1> <br>
            <h1 id="myH1">Projets de Fin d'Etude</h1> <br>
        
            <div>
                <img src="imgApropos.webp">
            </div>
            
            <div id="myPs">
                <p>Le projet de fin d'étude ou le mémoire de fin d'études est un travail écrit, obligatoire dans les formations de niveau licence (PFE) ou master (mémoire). Il s'agit de réaliser un travail de réflexion et d'analyse rigoureux à partir d'une question en lien avec la filière paramédicale choisie.</p>
                <br>
                <p>C'est un travail d'initiation à la recherche. Le PFE est rédigé en fin de licence alors que le mémoire est rédigé en fin de master et consiste en une recherche et une étude plus approfondie.</p>
                <br>
                <p>Dans les deux cas, l'étudiant est amené à élaborer une problématique à partir d'une situation professionnelle spécifique. Après la collecte des données empiriques et théoriques, leur analyse et leur mise en relation, l'étudiant va traduire le cheminement de sa réflexion autour d'une problématique.</p>
            </div>

            <h3 id="ProposH1">A propos de l'application :</h3>

            <div id="myPs">
                <p>Notre application va vous aider en tant qu'étudiant à trouver des exemples de PFE selon votre filière choisie et aussi selon le domaine sur lequel vous voulez travailler votre PFE, à trouver des ressources pédagogiques pour découvrir des articles scientifiques qui vont vous aider lors de votre réalisation de PFE, ainsi de choisir un sujet parmi ceux proposés par votre encadrant, Comme vous allez contacter votre encadrant et voir son feedback sur votre Rapport PFE  après avoir le déposer dans l'application.</p>
                <br>
                <p>L'application va vous aider en tant qu'encadrant à contacter vos groupes d'étudiants, proposer des sujets de PFE à eux, valider ou invalider leur Rapport déposé et donner un feedback.</p>
                <br>
                <p>En tant que responsable des PFE, vous allez déposer la liste Encadrant/Groupe d'étudiant avec les emails des encadrants.</p>
            
            </div>

            
        </div>

       
    </div>
</body>
</html>