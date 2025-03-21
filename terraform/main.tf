provider "azurerm" {
  features {}
  subscription_id = var.subscription_id
}

# Virtual Network
resource "azurerm_virtual_network" "vnet_internal_001" {
  name                = "vnet-internal-001"
  resource_group_name = "rg-group-015"
  location            = "spaincentral"
  address_space       = ["10.0.0.0/16"]
}

# Subnet for VM 1
resource "azurerm_subnet" "internal" {
  name                 = "snet-internal-001"
  resource_group_name  = "rg-group-015"
  virtual_network_name = azurerm_virtual_network.vnet_internal_001.name
  address_prefixes     = ["10.0.1.0/24"]
}

# Public IP for VM 1
resource "azurerm_public_ip" "ip_public_vm1_001" {
  name                = "ip-public-vm1-001"
  resource_group_name = "rg-group-015"
  location            = "spaincentral"
  allocation_method   = "Static"
  sku                 = "Standard"
}

# Network Interface
resource "azurerm_network_interface" "nic_vm_kub_001" {
  name                = "nic-vm-kub-001"
  resource_group_name = "rg-group-015"
  location            = "spaincentral"

  ip_configuration {
    name                          = "ipconfig"
    subnet_id                     = azurerm_subnet.internal.id
    private_ip_address_allocation = "Dynamic"
    public_ip_address_id          = azurerm_public_ip.ip_public_vm1_001.id
  }
}

resource "azurerm_virtual_machine" "vm_kub_001" {
  name                = "vm-kub-001"
  resource_group_name = "rg-group-015"
  location            = "spaincentral"
  vm_size             = "Standard_B2ls_v2"

  network_interface_ids = [
    azurerm_network_interface.nic_vm_kub_001.id
  ]
  primary_network_interface_id = azurerm_network_interface.nic_vm_kub_001.id

  delete_os_disk_on_termination = true
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
    name                      = "vm-kub-001_OsDisk_1"
    caching                   = "ReadWrite"
    create_option             = "FromImage"
    managed_disk_type         = "Standard_LRS"
    disk_size_gb              = 30
    os_type                   = "Linux"
    write_accelerator_enabled = false
  }

  os_profile {
    computer_name  = "vm-kub-001"
    admin_username = "adminuser"
    admin_password = "Password1234!"
  }

  os_profile_linux_config {
    disable_password_authentication = false
  }

  tags = {
    environment = "production"
  }
}

# Public IP for VM 2
resource "azurerm_public_ip" "ip_public_vm2_001" {
  name                = "ip-public-vm2-001"
  resource_group_name = "rg-group-015"
  location            = "spaincentral"
  allocation_method   = "Static"
  sku                 = "Standard"
}

# Network Interface for VM 2
resource "azurerm_network_interface" "nic_vm_kub_002" {
  name                = "nic-vm-kub-002"
  resource_group_name = "rg-group-015"
  location            = "spaincentral"

  ip_configuration {
    name                          = "ipconfig"
    subnet_id                     = azurerm_subnet.internal.id
    private_ip_address_allocation = "Dynamic"
    public_ip_address_id          = azurerm_public_ip.ip_public_vm2_001.id
  }
}

# VM 2
resource "azurerm_virtual_machine" "vm_kub_002" {
  name                = "vm-kub-002"
  resource_group_name = "rg-group-015"
  location            = "spaincentral"
  vm_size             = "Standard_B2ls_v2"

  network_interface_ids = [
    azurerm_network_interface.nic_vm_kub_002.id
  ]
  primary_network_interface_id = azurerm_network_interface.nic_vm_kub_002.id

  delete_os_disk_on_termination = true
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
    name                      = "vm-kub-002_OsDisk_1"
    caching                   = "ReadWrite"
    create_option             = "FromImage"
    managed_disk_type         = "Standard_LRS"
    disk_size_gb              = 30
    os_type                   = "Linux"
    write_accelerator_enabled = false
  }

  os_profile {
    computer_name  = "vm-kub-002"
    admin_username = "adminuser"
    admin_password = "Password1234!"
  }

  os_profile_linux_config {
    disable_password_authentication = false
  }

  tags = {
    environment = "production"
  }
}
