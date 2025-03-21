# Azure VM Management with Terraform

## Introduction

This repository contains Terraform configuration for deploying and managing Azure virtual machines and related infrastructure. [Terraform](https://www.terraform.io/) is an Infrastructure as Code (IaC) tool that allows you to define and provision infrastructure using a declarative configuration language. With Terraform, you can version, reuse, and share your infrastructure configurations.

This project specifically sets up:
- Virtual networks and subnets in Azure
- Network interfaces and public IPs
- Multiple Linux virtual machines using a scalable approach
- Integration with HCP Vault for secrets management
- Remote state storage in Azure

## How Terraform Works

Terraform uses a declarative approach where you specify the desired end state of your infrastructure. The key workflow is:

1. Write configuration files that describe your infrastructure
2. Run `terraform plan` to see what changes will be made
3. Run `terraform apply` to create or update your infrastructure
4. Use `terraform destroy` when you want to tear down resources

## Prerequisites

- [Terraform](https://www.terraform.io/downloads.html) 1.0+ installed
- Azure CLI installed and configured (`az login`)
- Access to company-managed resource group "rg-group-015"
- Access to HCP Vault with necessary secrets configured

## Architecture

- **Virtual Network**: Single VNet with a dedicated subnet
- **Virtual Machines**: Multiple Ubuntu VMs defined through a collection
- **Authentication**: SSH keys stored in HCP Vault
- **State Management**: Remote state in Azure Storage

## Required Secrets in HCP Vault

The following secrets must be configured in the "azure-credentials" app in HCP Vault:
- `ssh_admin_public_key`: SSH public key for admin access
- `ssh_ansible_public_key`: SSH public key for Ansible
- `subscription_id`: Azure subscription ID

## Setup

1. **Clone this repository**:

   ```bash
   git clone <repository-url>
   cd <repository-directory>
   ```

2. **Set required variables**:

   The `hcp_project_id` variable is required and can be set using one of these methods:

   **Option 1:** Create a `terraform.tfvars` file (recommended for local development):

   ```terraform
   hcp_project_id = "your-hcp-project-id"

   # Optional overrides for default variables if needed
   # location = "westeurope"
   # resource_group_name = "different-resource-group"
   # vm_size = "Standard_D4s_v3"
   ```

   **Option 2:** Use environment variables:

   ```bash
   export TF_VAR_hcp_project_id="your-hcp-project-id"
   ```

   **Option 3:** Provide it directly on the command line:

   ```bash
   terraform plan -var="hcp_project_id=your-hcp-project-id"
   ```

3. **Initialize Terraform**:

   ```bash
   terraform init
   ```

   This downloads the necessary providers and sets up the backend.

4. **Review the Planned Changes**:

   ```bash
   terraform plan
   ```

   This shows what resources will be created, modified, or destroyed.

5. **Apply Configuration**:

   ```bash
   terraform apply
   ```

   Type 'yes' when prompted to create the resources.

## Accessing Secrets from HashiCorp Vault Secrets (HVS)

You can retrieve your Azure subscription ID and other secrets from HashiCorp Vault Secrets using multiple methods:

### Using the HCP CLI

1. **Install the CLI**:

   Homebrew is a package manager for macOS.
   ```bash
   brew tap hashicorp/tap
   brew install hashicorp/tap/hcp
   ```
   More installation options are available on the HashiCorp website.

2. **Setup**:

   a. Login to the HashiCorp Cloud Platform to access HVS.
   ```bash
   hcp auth login
   ```

   b. Once successfully logged in run:
   ```bash
   hcp profile init --vault-secrets
   ```

   c. Now set your default config by selecting Organization, Project, and App.

3. **Read your secret**:

   ```bash
   hcp vault-secrets secrets open {desired secret}
   ```

   You may also inject secrets into your app as environment variables by passing a command as string, as shown below for an app using python.
   ```bash
   hcp vault-secrets run -- python3 my_app.py
   ```

### Using the Web UI

You can also log into the HashiCorp Cloud Platform web interface to access and manage your secrets through a user-friendly dashboard.

## Project Structure

- `main.tf` - Contains the main resource definitions for Azure VMs and networking
- `variables.tf` - Defines input variables used in the configuration
- `backend.tf` - Configures Terraform state storage in Azure
- `terraform.tfvars` - Used to set required variables (not committed to version control)
- `example.tfvars` - Example variable file with placeholder values (safe to commit)

## Variables

| Name | Description | Default |
|------|-------------|---------|
| location | Azure region to deploy resources | spaincentral |
| resource_group_name | Name of the resource group | rg-group-015 |
| vm_size | Size of the VMs | Standard_B2ls_v2 |
| hcp_project_id | HCP project ID | (must be provided) |

## Recent Improvements

- Replaced hardcoded values with variables for better maintainability
- Used for_each to manage multiple VMs with less code duplication
- Added data source reference to company-managed resource group
- Configured remote state in Azure Storage
- Improved code organization and structure

## Security Best Practices

- Never commit sensitive credentials to version control
- Add `*.tfvars` to your `.gitignore` file (except for example files)
- Use environment variables for secrets in CI/CD pipelines
- Store state files securely using the Azure backend
- Review access permissions to your Azure resources regularly

## Troubleshooting

If you encounter issues:
- Ensure Azure CLI is properly authenticated
- Verify your subscription has sufficient permissions
- Check that the HCP Vault secrets are correctly configured
- Review Terraform logs for detailed error information
