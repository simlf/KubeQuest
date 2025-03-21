terraform {
  backend "azurerm" {
    resource_group_name  = "rg-group-015"
    storage_account_name = "bucketterraform"
    container_name       = "tfstate"
    key                  = "terraform.tfstate"
  }
}
