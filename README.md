# WHM Auto Updater

A Laravel-based automation system that seamlessly deploys feature updates and bug fixes across multiple client websites through WHM/cPanel integration.

## What it does

- Automatically checks for new updates in the main development site (HotashKom)
- Verifies client maintenance package status through WHMCS API
- Syncs approved updates to client websites with active maintenance packages
- Manages file deployments and database migrations
- Provides deployment logs and status tracking
- Handles rollbacks if deployment fails

## Key Features

- **Automated Deployment**: Push updates to multiple client sites simultaneously
- **WHMCS Integration**: Validates maintenance package status before updates
- **Smart Sync**: Only deploys relevant changes and new features
- **Security**: Implements secure WHM/cPanel API communication
- **Logging**: Tracks all deployment activities and their status
- **Error Handling**: Automatic rollback system for failed deployments

## System Architecture

- Laravel Framework
- WHM/cPanel API Integration
- WHMCS API Integration
- MySQL/MariaDB Database
- Queue System for Background Jobs