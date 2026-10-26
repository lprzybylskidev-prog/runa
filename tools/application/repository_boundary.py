"""Compatibility policy API; implementation receives explicit process/environment dependencies."""
import os
import subprocess
from tools.application.repository_boundary_service import RepositoryPolicy, RepositoryBoundaryError, DevelopmentContextError, OWNER_PATHS

def receipt(root):
    return RepositoryPolicy(subprocess.run, dict(os.environ)).receipt(root)

def assert_installation(root):
    return RepositoryPolicy(subprocess.run, dict(os.environ)).assert_installation(root)

def application_remote(root, data=None):
    return RepositoryPolicy(subprocess.run, dict(os.environ)).application_remote(root, data)

def named_application_remote(root, name, data):
    return RepositoryPolicy(subprocess.run, dict(os.environ)).named_application_remote(root, name, data)

def assert_production_installation(root):
    return RepositoryPolicy(subprocess.run, dict(os.environ)).assert_production_installation(root)

def assert_development_context(root):
    return RepositoryPolicy(subprocess.run, dict(os.environ)).assert_development_context(root)

def write_staging_receipt(root, data):
    return RepositoryPolicy(subprocess.run, dict(os.environ)).write_staging_receipt(root, data)

def prepare_customer_context(root, mode, remote='origin'):
    return RepositoryPolicy(subprocess.run, dict(os.environ)).prepare_customer_context(root, mode, remote)
