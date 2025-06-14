# Prometheus-Grafana Chart

This Helm chart deploys a complete monitoring and logging stack for Kubernetes, including Prometheus, Grafana, and Loki.

## Components

- **Prometheus**: Metrics collection and storage
- **Grafana**: Metrics and logs visualization
- **Loki**: Log collection and analysis
- **AlertManager**: Alert management (disabled by default)

## Configuration

### Grafana
- Accessible via ingress at `grafana.local.tcloud`
- Default credentials:
  - Username: `admin`
  - Password: `admin`
- SSL configuration with passthrough

### Loki
- Integrated as a data source in Grafana
- Accessible at `http://loki:3100`
- ServiceMonitor configured for metrics collection

## Installation

The chart is deployed via ArgoCD. The configuration can be found in `argocdchart/templates/prometheus-grafana-app.yaml`.

### Prerequisites
- Kubernetes cluster
- ArgoCD installed
- Ingress Controller (nginx)

## File Structure

```
prometheus-grafana-chart/
├── Chart.yaml           # Chart definition and dependencies
├── values.yaml          # Default configuration
├── ingress-grafana.yaml # Grafana ingress configuration
├── .helmignore         # Files ignored by Helm
└── overlays/           # Additional configurations
    ├── loki-datasource.yaml
    ├── loki-servicemonitor.yaml
    └── grafana-deployment-patch.yaml
```

## Security

- Services are in ClusterIP mode to limit external exposure
- Ingress configured with SSL passthrough
- It is recommended to change the Grafana admin password after deployment

## Maintenance

### Useful Commands

```bash
# Check application status in ArgoCD
kubectl get applications -n argocd prometheus-grafana

# Check pods
kubectl get pods -n monitoring

# Check ingress
kubectl get ingress -n monitoring grafana-ingress

# Check Loki configuration
kubectl get configmap -n monitoring loki-datasource
```

## Customization

Parameters can be modified in the `values.yaml` file. Main options include:

- Grafana configuration (password, ingress)
- AlertManager enable/disable
- Service configuration (ClusterIP by default) 