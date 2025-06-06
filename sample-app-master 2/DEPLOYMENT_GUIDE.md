# Guide de Déploiement KubeQuest avec Health Probes

## 🎯 Résumé des modifications

Votre application Laravel **KubeQuest** a été configurée avec les Health Probes Kubernetes. Voici ce qui a été ajouté :

### ✅ Fichiers créés/modifiés

1. **`app/Http/Controllers/HealthController.php`** - Contrôleur Laravel pour les health checks
2. **`routes/web.php`** - Routes health checks ajoutées
3. **`routes/api.php`** - Routes API health checks + correction syntaxe
4. **`app/Http/Middleware/HealthCheckMiddleware.php`** - Middleware pour les health checks
5. **`health-test.php`** - Script de test des endpoints
6. **Chart Helm mis à jour** avec les probes

## 🚀 Endpoints configurés

| Endpoint | Type | Description |
|----------|------|-------------|
| `/health/startup` | Startup Probe | Vérifie le démarrage de l'app |
| `/health/ready` | Readiness Probe | Vérifie si l'app peut recevoir du trafic |
| `/health/live` | Liveness Probe | Vérifie si l'app fonctionne correctement |
| `/api/health/*` | API versions | Mêmes checks via API |

## 🔍 Health Checks spécifiques à KubeQuest

### Startup Probe
- ✅ Configuration Laravel chargée
- ✅ Connexion base de données (MySQL)
- ✅ Système de fichiers accessible
- ✅ Services Laravel opérationnels

### Readiness Probe
- ✅ Base de données responsive
- ✅ Table `counters` accessible
- ✅ Cache fonctionnel
- ✅ Ressources système suffisantes
- ✅ Extensions PHP requises

### Liveness Probe
- ✅ Processus PHP actif
- ✅ Mémoire sous contrôle
- ✅ Fonctionnalité Counter de l'app
- ✅ Laravel en état nominal

## 🛠️ Tests avant déploiement

### 1. Test local avec Artisan
```bash
cd sample-app-master\ 2
php artisan serve
```

### 2. Test des endpoints
```bash
# Utiliser le script de test
php health-test.php

# Ou manuellement
curl http://localhost:8000/health/startup
curl http://localhost:8000/health/ready
curl http://localhost:8000/health/live
```

### 3. Exemple de réponse attendue
```json
{
    "status": "healthy",
    "timestamp": "2024-01-15T10:30:00Z",
    "checks": {
        "config": {
            "status": "healthy",
            "message": "Configuration loaded successfully"
        },
        "database": {
            "status": "healthy",
            "message": "Database connection successful",
            "response_time_ms": 2.5
        }
    },
    "app": "KubeQuest",
    "version": "1.0.0"
}
```

## 🐳 Mise à jour de votre image Docker

Assurez-vous que votre `Dockerfile` inclut les nouveaux fichiers :

```dockerfile
# Dans votre Dockerfile, après COPY . /var/www/html
COPY app/Http/Controllers/HealthController.php /var/www/html/app/Http/Controllers/
COPY app/Http/Middleware/HealthCheckMiddleware.php /var/www/html/app/Http/Middleware/
```

## ☸️ Déploiement Kubernetes

### 1. Construire l'image
```bash
# Depuis le dossier sample-app-master 2
docker build -t cley44/kubequest:1.3 .
docker push cley44/kubequest:1.3
```

### 2. Mettre à jour values.yaml
```yaml
image:
  tag: "1.3"  # Nouvelle version avec health checks
```

### 3. Déployer avec Helm
```bash
# Depuis la racine du projet
helm upgrade --install kubequest ./appchart
```

### 4. Vérifier le déploiement
```bash
kubectl get pods
kubectl describe pod <pod-name>
kubectl logs <pod-name>
```

## 🔧 Configuration des Probes

Les paramètres sont définis dans `appchart/values.yaml` :

```yaml
healthProbes:
  enabled: true
  startup:
    enabled: true
    httpGet:
      path: /health/startup
      port: 80
      scheme: HTTP
    initialDelaySeconds: 5
    periodSeconds: 5
    timeoutSeconds: 3
    failureThreshold: 30  # 150s max pour démarrage
  readiness:
    enabled: true
    httpGet:
      path: /health/ready
      port: 80
    initialDelaySeconds: 5
    periodSeconds: 10
    failureThreshold: 3
  liveness:
    enabled: true
    httpGet:
      path: /health/live
      port: 80
    initialDelaySeconds: 30
    periodSeconds: 30
    failureThreshold: 3
```

## 🚨 Troubleshooting

### Pod ne démarre pas
```bash
kubectl describe pod <pod-name>
kubectl logs <pod-name>
```

Vérifiez les erreurs de startup probe. Causes communes :
- Base de données non accessible
- Variables d'environnement manquantes
- Permissions filesystem

### Pod redémarre en boucle
La liveness probe échoue. Vérifiez :
- Utilisation mémoire
- Connexion DB
- Erreurs applicatives

### Pas de trafic vers le pod
La readiness probe échoue. Vérifiez :
- Services externes (MySQL)
- Configuration réseau
- Ressources système

## ✨ Fonctionnalités avancées

### Monitoring personnalisé
Les health checks incluent des métriques :
- Temps de réponse DB
- Utilisation mémoire
- Nombre d'enregistrements counters
- Versions Laravel/PHP

### Alerting
Intégrez avec votre système de monitoring pour être alerté des échecs de probes.

### Métriques Prometheus
Les endpoints retournent des données structurées exploitables par Prometheus.

---

🎉 **Votre application KubeQuest est maintenant prête pour la production avec des Health Probes robustes !** 