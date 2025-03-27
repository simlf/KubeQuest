terraform {
  required_providers {
    hcp = {
      source  = "hashicorp/hcp"
      version = "~> 0.76.0"
    }
  }
}

provider "azurerm" {
  features {}
  subscription_id = data.hcp_vault_secrets_secret.subscription_id.secret_value
}

provider "hcp" {
  project_id = var.hcp_project_id
}

# Fetch SSH keys from HCP Vault Secrets
data "hcp_vault_secrets_secret" "admin_key" {
  app_name    = "azure-credentials"
  secret_name = "ssh_admin_public_key"
}

data "hcp_vault_secrets_secret" "ansible_key" {
  app_name    = "azure-credentials"
  secret_name = "ssh_ansible_public_key"
}

data "hcp_vault_secrets_secret" "subscription_id" {
  app_name    = "azure-credentials"
  secret_name = "subscription_id"
}

data "azurerm_resource_group" "rg" {
  name = var.resource_group_name
}

# Virtual Network
resource "azurerm_virtual_network" "vnet_kubernetes" {
  name                = "vnet-kubernetes"
  resource_group_name = data.azurerm_resource_group.rg.name
  location            = data.azurerm_resource_group.rg.location
  address_space       = ["10.0.0.0/16"]
}

# Subnet for master node
resource "azurerm_subnet" "subnet_master" {
  name                 = "snet-kubernetes-master"
  resource_group_name  = data.azurerm_resource_group.rg.name
  virtual_network_name = azurerm_virtual_network.vnet_kubernetes.name
  address_prefixes     = ["10.0.1.0/24"]
}

# Subnet for worker nodes
resource "azurerm_subnet" "subnet_worker" {
  name                 = "snet-kubernetes-worker"
  resource_group_name  = data.azurerm_resource_group.rg.name
  virtual_network_name = azurerm_virtual_network.vnet_kubernetes.name
  address_prefixes     = ["10.0.2.0/24"]
}

locals {
  vms = {
    "master" = { name = "vm-kub-master", nic = "nic-vm-kub-master", public_ip = "ip-public-vm-master" }
    "worker" = { name = "vm-kub-worker", nic = "nic-vm-kub-worker", public_ip = "ip-public-vm-worker" }
  }
}

resource "azurerm_public_ip" "ip_public_vm" {
  for_each            = local.vms
  name                = each.value.public_ip
  resource_group_name = data.azurerm_resource_group.rg.name
  location            = data.azurerm_resource_group.rg.location
  allocation_method   = "Static"
  sku                 = "Standard"
}

resource "azurerm_network_interface" "nic_vm_kub" {
  for_each            = local.vms
  name                = each.value.nic
  resource_group_name = data.azurerm_resource_group.rg.name
  location            = data.azurerm_resource_group.rg.location

  ip_configuration {
    name                          = "ipconfig"
    subnet_id                     = each.key == "master" ? azurerm_subnet.subnet_master.id : azurerm_subnet.subnet_worker.id
    private_ip_address_allocation = "Dynamic"
    public_ip_address_id          = azurerm_public_ip.ip_public_vm[each.key].id
  }
}

resource "azurerm_virtual_machine" "vm_kub" {
  for_each            = local.vms
  name                = each.value.name
  resource_group_name = data.azurerm_resource_group.rg.name
  location            = data.azurerm_resource_group.rg.location
  vm_size             = var.vm_size

  network_interface_ids = [
    azurerm_network_interface.nic_vm_kub[each.key].id
  ]
  primary_network_interface_id = azurerm_network_interface.nic_vm_kub[each.key].id

  delete_os_disk_on_termination    = true
  delete_data_disks_on_termination = false

  boot_diagnostics {
    enabled     = false
    storage_uri = "https://bucketterraform.blob.core.windows.net/"
  }

  storage_image_reference {
    publisher = "Canonical"
    offer     = "0001-com-ubuntu-server-jammy"
    sku       = "22_04-lts"
    version   = "latest"
  }

  storage_os_disk {
    name                      = "${each.value.name}_OsDisk_1"
    caching                   = "ReadWrite"
    create_option             = "FromImage"
    managed_disk_type         = "Standard_LRS"
    disk_size_gb              = 30
    os_type                   = "Linux"
    write_accelerator_enabled = false
  }

  os_profile {
    computer_name  = each.value.name
    admin_username = "adminuser"
  }

  os_profile_linux_config {
    disable_password_authentication = true

    # Admin key
    ssh_keys {
      path     = "/home/adminuser/.ssh/authorized_keys"
      key_data = data.hcp_vault_secrets_secret.admin_key.secret_value
    }

    # Ansible key
    ssh_keys {
      path     = "/home/adminuser/.ssh/authorized_keys"
      key_data = data.hcp_vault_secrets_secret.ansible_key.secret_value
    }
  }

  tags = {
    environment = "production"
  }
}

# Network Security Groups for Kubernetes
resource "azurerm_network_security_group" "nsg_kub_master" {
  name                = "nsg-kub-master"
  location            = data.azurerm_resource_group.rg.location
  resource_group_name = data.azurerm_resource_group.rg.name

  # SSH access
  security_rule {
    name                       = "SSH"
    priority                   = 1000
    direction                  = "Inbound"
    access                     = "Allow"
    protocol                   = "Tcp"
    source_port_range          = "*"
    destination_port_range     = "22"
    source_address_prefix      = "*"
    destination_address_prefix = "*"
  }

  # Kubernetes API server
  security_rule {
    name                       = "Kubernetes-API-Server"
    priority                   = 1001
    direction                  = "Inbound"
    access                     = "Allow"
    protocol                   = "Tcp"
    source_port_range          = "*"
    destination_port_range     = "6443"
    source_address_prefix      = "*"
    destination_address_prefix = "*"
  }

  # etcd server client API
  security_rule {
    name                       = "Etcd-server"
    priority                   = 1002
    direction                  = "Inbound"
    access                     = "Allow"
    protocol                   = "Tcp"
    source_port_range          = "*"
    destination_port_range     = "2379-2380"
    source_address_prefix      = "*"
    destination_address_prefix = "*"
  }

  # Kubelet API
  security_rule {
    name                       = "Kubelet-API"
    priority                   = 1003
    direction                  = "Inbound"
    access                     = "Allow"
    protocol                   = "Tcp"
    source_port_range          = "*"
    destination_port_range     = "10250"
    source_address_prefix      = "*"
    destination_address_prefix = "*"
  }

  # kube-scheduler
  security_rule {
    name                       = "Kube-Scheduler"
    priority                   = 1004
    direction                  = "Inbound"
    access                     = "Allow"
    protocol                   = "Tcp"
    source_port_range          = "*"
    destination_port_range     = "10259"
    source_address_prefix      = "*"
    destination_address_prefix = "*"
  }

  # kube-controller-manager
  security_rule {
    name                       = "Kube-Controller-Manager"
    priority                   = 1005
    direction                  = "Inbound"
    access                     = "Allow"
    protocol                   = "Tcp"
    source_port_range          = "*"
    destination_port_range     = "10257"
    source_address_prefix      = "*"
    destination_address_prefix = "*"
  }

  tags = {
    environment = "production"
  }
}

resource "azurerm_network_security_group" "nsg_kub_worker" {
  name                = "nsg-kub-worker"
  location            = data.azurerm_resource_group.rg.location
  resource_group_name = data.azurerm_resource_group.rg.name

  # SSH access
  security_rule {
    name                       = "SSH"
    priority                   = 1000
    direction                  = "Inbound"
    access                     = "Allow"
    protocol                   = "Tcp"
    source_port_range          = "*"
    destination_port_range     = "22"
    source_address_prefix      = "*"
    destination_address_prefix = "*"
  }

  # Kubelet API
  security_rule {
    name                       = "Kubelet-API"
    priority                   = 1001
    direction                  = "Inbound"
    access                     = "Allow"
    protocol                   = "Tcp"
    source_port_range          = "*"
    destination_port_range     = "10250"
    source_address_prefix      = "*"
    destination_address_prefix = "*"
  }

  # kube-proxy
  security_rule {
    name                       = "Kube-Proxy"
    priority                   = 1002
    direction                  = "Inbound"
    access                     = "Allow"
    protocol                   = "Tcp"
    source_port_range          = "*"
    destination_port_range     = "10256"
    source_address_prefix      = "*"
    destination_address_prefix = "*"
  }

  # NodePort Services
  security_rule {
    name                       = "NodePort-Services"
    priority                   = 1003
    direction                  = "Inbound"
    access                     = "Allow"
    protocol                   = "Tcp"
    source_port_range          = "*"
    destination_port_range     = "30000-32767"
    source_address_prefix      = "*"
    destination_address_prefix = "*"
  }

  tags = {
    environment = "production"
  }
}

# Associate NSGs with network interfaces
resource "azurerm_network_interface_security_group_association" "master_nsg_association" {
  network_interface_id      = azurerm_network_interface.nic_vm_kub["master"].id
  network_security_group_id = azurerm_network_security_group.nsg_kub_master.id
}

resource "azurerm_network_interface_security_group_association" "worker_nsg_association" {
  network_interface_id      = azurerm_network_interface.nic_vm_kub["worker"].id
  network_security_group_id = azurerm_network_security_group.nsg_kub_worker.id
}
