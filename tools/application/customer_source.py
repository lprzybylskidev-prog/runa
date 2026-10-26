"""Compatibility policy API; implementation receives explicit process/environment dependencies."""
import os
import subprocess
from tools.application.customer_source_service import CustomerSource, COMPOSED

def application_path(path):
    return CustomerSource(subprocess.run, dict(os.environ)).application_path(path)

def excluded(path):
    return CustomerSource(subprocess.run, dict(os.environ)).excluded(path)

def git(root, *arguments):
    return CustomerSource(subprocess.run, dict(os.environ)).git(root, *arguments)

def validate_target(target):
    return CustomerSource(subprocess.run, dict(os.environ)).validate_target(target)

def tree(root, ref):
    return CustomerSource(subprocess.run, dict(os.environ)).tree(root, ref)

def validate_release(installed):
    return CustomerSource(subprocess.run, dict(os.environ)).validate_release(installed)

def fetch(root, state, target='main', installed=None):
    return CustomerSource(subprocess.run, dict(os.environ)).fetch(root, state, target, installed)
