# Students City API

Application Symfony pour la gestion d'établissements, incluant les fonctionnalités suivantes :

- Création et consultation d'établissements
- Ajout, modification et suppression d’avis
- Carte interactive avec position GPS des lieux
- Interface d’administration pour modération
- Authentification

---

## 🛠️ Technologies

- Symfony
- Doctrine ORM
- Twig & Tailwind CSS
- Google Maps
- JavaScript
- Sqlite

---

## 🚀 Installation

```bash
git clone https://github.com/ton-repo/students-city-api.git
cd students-city-api
composer install
```

Configure .env avec tes identifiants BDD :

```.env
DATABASE_URL="mysql://user:password@127.0.0.1:3306/students_city"
````

Puis crée ta base de données et exécute les migrations :

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

⸻

⚙️ Lancer le projet

```bash
symfony server:start
```

Ou :

```bash
php -S localhost:8000 -t public
```

⸻

👤 Rôles utilisateurs

    •	ROLE_USER : peut créer un établissement et laisser des avis.
	•	ROLE_ADMIN : peut valider, modifier ou supprimer tout contenu.

⸻

---

## 📬 Exemples de requêtes API

###  📝 Inscription (POST /api/register)
```bash
curl -X POST http://localhost:8000/api/register \
     -H "Content-Type: application/json" \
     -d '{"username": "user", "email": "user@example.com", "password": "motdepasse"}'
```

### 🔐 Connexion (POST /api/login)
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email": "user@example.com", "password": "motdepasse"}'
```

### 👤 Infos utilisateur connecté (GET /api/me)

```bash
curl -X GET http://localhost:8000/api/me \
  -H "Authorization: Bearer <TOKEN>"
```