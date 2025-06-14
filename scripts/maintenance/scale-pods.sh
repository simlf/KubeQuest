#!/bin/bash

ACTION=$1

if [[ "$ACTION" != "down" && "$ACTION" != "up" ]]; then
  echo "Usage: $0 [down|up]"
  exit 1
fi

if [ "$ACTION" == "down" ]; then
  echo "Scaling down pods to 0..."
  kubectl scale deployment monitoring-grafana -n default --replicas=0
  kubectl scale deployment dashboard-metrics-scraper -n kubernetes-dashboard --replicas=0
  kubectl scale deployment kubernetes-dashboard -n kubernetes-dashboard --replicas=0
  kubectl scale deployment app-appchart -n app --replicas=0
  kubectl scale statefulset mysql-db -n default --replicas=0
  kubectl scale deployment argocd-server -n argocd --replicas=0
else
  echo "Restoring pods to 1 replica..."
  kubectl scale deployment monitoring-grafana -n default --replicas=1
  kubectl scale deployment dashboard-metrics-scraper -n kubernetes-dashboard --replicas=1
  kubectl scale deployment kubernetes-dashboard -n kubernetes-dashboard --replicas=1
  kubectl scale deployment app-appchart -n app --replicas=1
  kubectl scale statefulset mysql-db -n default --replicas=1
  kubectl scale deployment argocd-server -n argocd --replicas=1
fi
