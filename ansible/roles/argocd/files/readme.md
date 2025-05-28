cat mysql-secret.yml | kubeseal \
    --controller-namespace kube-system \
    --controller-name sealed-secrets \
    --format yaml \
    > sealed-secret.yml

KUBESEAL_VERSION='0.24.0'
wget "https://github.com/bitnami-labs/sealed-secrets/releases/download/v${KUBESEAL_VERSION:?}/kubeseal-${KUBESEAL_VERSION:?}-linux-amd64.tar.gz"
tar -xvzf kubeseal-${KUBESEAL_VERSION:?}-linux-amd64.tar.gz kubeseal
sudo install -m 755 kubeseal /usr/local/bin/kubeseal


kubeseal \
  --cert mypublickey.pem \
  --controller-namespace kube-system \
  --controller-name sealed-secrets \
  < mysql-secret.yaml > mysql-sealedsecret.yaml