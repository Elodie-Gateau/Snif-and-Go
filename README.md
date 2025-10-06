![Symfony](https://img.shields.io/badge/Symfony-7.3-blue)
![PHP](https://img.shields.io/badge/PHP-8.4-blueviolet)
![MySQL](https://img.shields.io/badge/DB-MySQL-orange)
![License](https://img.shields.io/badge/license-MIT-green)
![Status](https://img.shields.io/badge/status-deployed-success)

# Snif & Go 🐾

Application web communautaire permettant aux propriétaires de chiens de créer, rejoindre et gérer des balades via des itinéraires GPX.  

🌐 [Voir le site en ligne](https://dev02.arinfo.ovh/)

---

## 📁 Structure du dépôt (projets / dossiers)

Le dépôt contient principalement :

- **backend/** — Le cœur de l’application (Symfony, PHP, logique métier, sécurité)  
- **public/** — Le point d’entrée web, les assets front (CSS, JS, images)  
- **templates/** — Vues Twig  
- **src/** — Code source (contrôleurs, entités, services)  
- **config/** — Configuration Symfony  
- **migrations/** — Migrations Doctrine  
- **fichiers .env** — Configuration de l’environnement  

---

## 🛠 Installation & mise en route

### Prérequis

- PHP 8.4  
- Composer  
- MySQL ou autre base compatible Doctrine  
- Symfony CLI (facultatif mais recommandé)  

### Étapes

1. **Cloner le dépôt**
   ```bash
   git clone https://github.com/ton-compte/snif-and-go.git
   cd snif-and-go
   ```

2. **Installer les dépendances**
   ```bash
   composer install
   ```

3. **Configurer l’environnement**
   Copier `.env` vers `.env.local` et ajuster la variable `DATABASE_URL` :
   ```env
   # Exemple
   DATABASE_URL="mysql://user:password@127.0.0.1:3306/snifandgo"
   ```

4. **Créer la base de données et lancer les migrations**
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

5. **Lancer le serveur local**
   ```bash
   symfony server:start
    ```

---

## ✅ Fonctionnalités principales

- Gestion des utilisateurs : inscription, connexion, désactivation  
- Profils de chiens (avec statut, numéro d’identification)  
- Import / parsing de fichiers GPX pour définir des itinéraires  
- Création et participation à des balades  

---

## 🧪 Tests & Qualité

- Tests unitaires sur les services critiques (parsing GPX, calculs)  
- Vérifications fonctionnelles avant chaque itération  

---


## 📄 Licence & usage

Ce projet est développé dans le cadre du Titre Professionnel **Développeur Web et Web Mobile (DWWM)** à des fins pédagogiques.
Développé par Elodie Gateau

---

## 📬 Auteur

**Élodie Gateau**  
Développeuse Web en formation — Promotion DWWM 2025  
