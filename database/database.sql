CREATE DATABASE gestionPFE;
USE gestionPFE;

-- Tables de base
CREATE TABLE Domaine (
    idDomaine INT AUTO_INCREMENT PRIMARY KEY,
    nomDomaine VARCHAR(50) NOT NULL
);

CREATE TABLE Filiere (
    idFiliere INT AUTO_INCREMENT PRIMARY KEY,
    nomFiliere VARCHAR(50) NOT NULL
);

CREATE TABLE SujetPFE (
    idSujetPFE INT AUTO_INCREMENT PRIMARY KEY,
    titreSujetPFE VARCHAR(100),
    descriptions TEXT,
    dateProposition DATE
);

CREATE TABLE Rapport (
    idRapport INT AUTO_INCREMENT PRIMARY KEY,
    titreRapport VARCHAR(100),
    dateDepot DATE
);

CREATE TABLE Feedback (
    idFeedback INT AUTO_INCREMENT PRIMARY KEY,
    contenu TEXT,
    dateFeedback DATE
);

-- Encadrant
CREATE TABLE Encadrant (
    idEncadrant INT AUTO_INCREMENT PRIMARY KEY,
    nomEncadrant VARCHAR(50),
    prenomEncadrant VARCHAR(50),
    cinEncadrant VARCHAR(50) UNIQUE,
    emailEncadrant VARCHAR(100) UNIQUE,
    motpasseEncadrant VARCHAR(255),
    specialite VARCHAR(50)
);

-- Groupe
CREATE TABLE Groupe (
    idGroupe INT AUTO_INCREMENT PRIMARY KEY,
    numGroupe VARCHAR(50),
    capacite INT,
    idEncadrant INT,
    FOREIGN KEY (idEncadrant) REFERENCES Encadrant(idEncadrant)
);

-- Etudiant
CREATE TABLE Etudiant (
    idEtudiant INT AUTO_INCREMENT PRIMARY KEY,
    cinEtudiant VARCHAR(50) UNIQUE,
    cneEtudiant VARCHAR(50) UNIQUE,
    nomEtudiant VARCHAR(50),
    prenomEtudiant VARCHAR(50),
    emailEtudiant VARCHAR(100) UNIQUE,
    motpasseEtudiant VARCHAR(255),
    dateNaissance DATE,
    idFiliere INT,
    idGroupe INT,
    FOREIGN KEY (idFiliere) REFERENCES Filiere(idFiliere),
    FOREIGN KEY (idGroupe) REFERENCES Groupe(idGroupe)
);

-- Choix sujet
CREATE TABLE Choisir (
    idGroupe INT UNIQUE,
    idSujetPFE INT UNIQUE,
    PRIMARY KEY (idGroupe, idSujetPFE),
    FOREIGN KEY (idGroupe) REFERENCES Groupe(idGroupe),
    FOREIGN KEY (idSujetPFE) REFERENCES SujetPFE(idSujetPFE)
);

-- Dépôt rapport
CREATE TABLE Deposer (
    idGroupe INT PRIMARY KEY,
    idRapport INT,
    idFeedback INT,
    FOREIGN KEY (idGroupe) REFERENCES Groupe(idGroupe),
    FOREIGN KEY (idRapport) REFERENCES Rapport(idRapport),
    FOREIGN KEY (idFeedback) REFERENCES Feedback(idFeedback)
);

CREATE TABLE Administrateur (
    idAdministrateur INT AUTO_INCREMENT PRIMARY KEY,
    cinAdministrateur VARCHAR(50) UNIQUE NOT NULL,
    nomAdministrateur VARCHAR(50) NOT NULL,
    prenomAdministrateur VARCHAR(50) NOT NULL,
    emailAdministrateur VARCHAR(100) UNIQUE NOT NULL,
    motpasseAdministrateur VARCHAR(255) NOT NULL,
    dernierAcces DATETIME
);

CREATE TABLE ResponsablePFE(
    idResponsable INT AUTO_INCREMENT PRIMARY KEY,
    cinResponsable VARCHAR(50) UNIQUE NOT NULL,
    nomResponsable VARCHAR(50) NOT NULL,
    prenomResponsable VARCHAR(50) NOT NULL,
    emailResponsable VARCHAR(100) UNIQUE,
    motpasseResponsable VARCHAR(255) NOT NULL
);

CREATE TABLE Ressources (
    idRessources INT AUTO_INCREMENT PRIMARY KEY,
    titreRessouces VARCHAR(100),
    url VARCHAR(255) NOT NULL,
    motsCles VARCHAR(255),
    idDomaine INT,
    FOREIGN KEY (idDomaine) REFERENCES Domaine(idDomaine)
);

CREATE TABLE Rechercher (
    idEtudiant INT,
    idExemplePFE INT,
    idRessources INT,
    dateConsultation DATE,
    PRIMARY KEY (idEtudiant, idExemplePFE, idRessources),
    FOREIGN KEY (idEtudiant) REFERENCES Etudiant(idEtudiant) ON DELETE CASCADE,
    FOREIGN KEY (idExemplePFE) REFERENCES ExemplePFE(idExemplePFE) ON DELETE CASCADE,
    FOREIGN KEY (idRessources) REFERENCES Ressources(idRessources) ON DELETE CASCADE
);

CREATE TABLE ExemplePFE (
    idExemplePFE INT AUTO_INCREMENT PRIMARY KEY,
    titreExemplePFE VARCHAR(150) NOT NULL,
    annee YEAR NOT NULL,
    idFiliere INT NOT NULL,
    idDomaine INT NOT NULL,
    
    chemin_fichier VARCHAR(255),  -- chemin du PDF
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (idDomaine) REFERENCES Domaine(idDomaine),
    FOREIGN KEY (idFiliere) REFERENCES Filiere(idFiliere),

    INDEX (idFiliere),
    INDEX (idDomaine)
);

INSERT INTO ExemplePFE (titreExemplePFE, annee, idFiliere, idDomaine, chemin_fichier)
VALUES ('Application mobile pour le tourisme', 2015, 1, 1, '/files/pfe1.pdf');
