# ArgoCD Configuration

This directory contains the ArgoCD configuration for the KubeQuest project.

## Directory Structure

```
argocd/
├── applications/           # ArgoCD Application definitions
│   └── kubernetes-dashboard/  # Kubernetes Dashboard applications
│       ├── kubernetes-dashboard.yaml
│       └── kubernetes-dashboard-rbac.yaml
└── values/                # Helm values for applications
    └── kubernetes-dashboard/
        └── values.yaml
```

## Applications

### Kubernetes Dashboard

The Kubernetes Dashboard is deployed using two applications in the `applications/kubernetes-dashboard/` directory:

1. `kubernetes-dashboard.yaml`: Main dashboard deployment
   - Uses the official Helm chart
   - Configured with NodePort service (port 30507)
   - Includes ingress configuration for dashboard.example.com

2. `kubernetes-dashboard-rbac.yaml`: RBAC configuration
   - Creates admin-user ServiceAccount
   - Sets up ClusterRoleBinding for admin access
   - Includes necessary labels for resource management

## Access

- Dashboard URL: https://dashboard.example.com:30507
- To get admin token:
  ```bash
  ssh -i ~/.ssh/t-cloud adminuser@<MASTER-PUBLIC-IP> "sudo kubectl -n kubernetes-dashboard create token admin-user"
  ```

## Configuration

All configuration values are stored in the `values/` directory, organized by application.
This separation allows for:
- Better version control
- Easier configuration management
- Clear separation of concerns

## Labels

All resources are labeled with:
- `app.kubernetes.io/name`: Application name
- `app.kubernetes.io/component`: Component type
- `app.kubernetes.io/part-of`: Parent application

## Troubleshooting

If you encounter issues with port forwarding:
1. Check if the port is already in use: `lsof -i :<port>`
2. Kill existing port-forward processes: `pkill -f "kubectl port-forward"`
3. Try a different local port
4. If you see "socat not found" error, install socat on the VM: `sudo apt-get install socat`
