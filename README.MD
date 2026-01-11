# Système de Gestion de Concessionnaire Automobile

Une application web dynamique développée en **PHP** et **MySQL** (compatible avec TiDB) pour la gestion d'une vitrine automobile. Cette plateforme permet aux visiteurs de consulter le catalogue et d'effectuer des réservations, tout en offrant aux administrateurs un tableau de bord sécurisé pour gérer l'inventaire et les interactions clients.

## Table des Matières
- [Fonctionnalités](#fonctionnalités)
- [Technologies Utilisées](#technologies-utilisées)
- [Structure de la Base de Données](#structure-de-la-base-de-données)
- [Installation et Configuration](#installation-et-configuration)
- [Utilisation](#utilisation)
- [Auteurs](#auteurs)

## Fonctionnalités

### Front-Office (Utilisateur / Client)
- **Catalogue de Voitures :** Consultation des véhicules disponibles avec images, prix et caractéristiques.
- **Recherche et Filtres :** Recherche par marque, ou fourchette de prix.
- **Détails du Véhicule :** Affichage complet de la fiche technique d'un véhicule spécifique.
- **Espace Membre :** Système d'inscription et de connexion pour les clients.
- **Système de Réservation :** Les clients peuvent demander des essais ou réserver des véhicules.
- **Formulaire de Contact :** Envoi de demandes directes à la concession.
- **Design Responsive :** Interface optimisée pour l'affichage sur ordinateur et mobile.

### Back-Office (Administrateur)
- **Tableau de Bord Sécurisé :** Vue d'ensemble du nombre de voitures, des messages non lus et des réservations en attente.
- **Gestion de l'Inventaire (CRUD) :**
  - Ajout de nouvelles voitures avec téléchargement d'images.
  - Modification des détails des véhicules existants.
  - Suppression des véhicules vendus ou retirés.
- **Centre de Messages :** Consultation et gestion des demandes clients.
- **Gestion des Réservations :** Approbation ou rejet des demandes de réservation.

## Technologies Utilisées
- **Backend :** PHP (Natif/PDO)
- **Base de Données :** MySQL / TiDB Cloud
- **Frontend :** HTML5, CSS3, Bootstrap (pour le style), JavaScript
- **Serveur :** Apache (via XAMPP/WAMP/Laragon)

## Structure de la Base de Données
Le projet utilise une base de données nommée `car_dealership` contenant les tables suivantes :
- `users` : Comptes administrateurs.
- `cars` : Données de l'inventaire automobile.
- `clients` : Comptes clients enregistrés.
- `bookings` : Enregistrements des réservations liant clients et voitures.
- `messages` : Soumissions du formulaire de contact.
- `reviews` : Avis et témoignages clients.
- `wishlist` : Voitures favorites des clients.