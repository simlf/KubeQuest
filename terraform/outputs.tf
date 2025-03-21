output "master_public_ip" {
  value = azurerm_public_ip.ip_public_vm["master"].ip_address
  description = "Public IP address of the Kubernetes master node"
}

output "worker_public_ip" {
  value = azurerm_public_ip.ip_public_vm["worker"].ip_address
  description = "Public IP address of the Kubernetes worker node"
}
