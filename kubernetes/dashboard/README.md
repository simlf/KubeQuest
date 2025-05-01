# Kubernetes Dashboard Setup

This directory contains the configuration for deploying the Kubernetes Dashboard in a secure and production-ready manner.

## Components

### 1. RBAC Configuration (`dashboard-rbac.yaml`)
- Creates a restricted service account for the dashboard
- Defines permissions to view:
  - Pods, Services, Nodes, Namespaces
  - Deployments, ReplicaSets, StatefulSets, DaemonSets
  - Jobs and CronJobs
- All permissions are read-only (get, list, watch)

### 2. Resource Configuration (`values.yaml`)
- Runs 2 replicas for high availability
- Sets resource limits and requests:
  - Dashboard: 200m CPU / 256Mi memory limit
  - Metrics Scraper: 100m CPU / 128Mi memory limit
- Configures health checks (liveness and readiness probes)
- Sets security context to run as non-root user

### 3. Network Policy (`network-policy.yaml`)
- Restricts network access to the dashboard
- Only allows traffic from within the kubernetes-dashboard namespace
- Only allows TCP traffic on port 8443

## User Roles

### Admin User
The admin user has full cluster access and is created for initial setup and emergency access:
```bash
# Create admin user
kubectl apply -f dashboard-adminuser.yaml

# Get admin token
kubectl -n kubernetes-dashboard create token admin-user
```

### Restricted User (dashboard-user)
The default user with limited read-only access to the cluster. This is the recommended user for daily operations:
```bash
# Get restricted user token
kubectl -n kubernetes-dashboard create token dashboard-user
```

## Access Methods

### Local Development
```bash
# Port forward to access the dashboard
kubectl -n kubernetes-dashboard port-forward svc/kubernetes-dashboard-kong-proxy 8443:443

# Get access token (choose based on role needed)
kubectl -n kubernetes-dashboard create token dashboard-user  # For restricted access
kubectl -n kubernetes-dashboard create token admin-user     # For admin access

# Access the dashboard at https://localhost:8443
```

### Production Access
For production environments, consider:
1. Setting up Ingress with TLS
2. Implementing OAuth2 authentication
3. Using a VPN or private network
4. Configuring proper monitoring and logging

## Security Notes
- The dashboard runs with minimal required permissions
- Network policies restrict access
- Non-root user security context
- Resource limits prevent resource exhaustion
- Admin user should be used sparingly and only for administrative tasks
- Regular users should use the restricted dashboard-user account

## Maintenance
- Monitor dashboard logs for issues
- Regularly rotate access tokens
- Keep the dashboard version updated
- Monitor resource usage
- Regularly audit admin user usage
- Consider implementing token expiration for admin access

## Troubleshooting
If the dashboard is not accessible:
1. Check if pods are running: `kubectl get pods -n kubernetes-dashboard`
2. Check pod logs: `kubectl logs -n kubernetes-dashboard <pod-name>`
3. Verify network policies: `kubectl get networkpolicy -n kubernetes-dashboard`
4. Check RBAC permissions: `kubectl get clusterrolebinding dashboard-user`
5. Verify admin user exists: `kubectl get serviceaccount -n kubernetes-dashboard admin-user`
