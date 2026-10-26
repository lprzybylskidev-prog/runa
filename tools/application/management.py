from tools.application.development_runtime import execution_location
"""Compatibility API for management callers migrating in RM-2026-012."""
from pathlib import Path
import os
from tools.application.management_service import ManagementService, MAX_OUTPUT, TIMEOUT, DEVELOPMENT_ACTIONS, TOOLS, OPERATIONS
from tools.application.processes import ApplicationProcesses
from tools.core.context import WorkspaceContext, ExecutionLocation
from tools.core.process import SubprocessRunner

ROOT = Path(__file__).resolve().parents[2]

def service():
    from tools.application.test_resources import endpoint
    return ManagementService(WorkspaceContext(ROOT), ApplicationProcesses(SubprocessRunner(), dict(os.environ)),
        execution_location(), endpoint)

def image_identity():
    return service().image_identity()

def ensure_image():
    return service().ensure_image()

def bounded_output(command):
    return service().bounded_output(command)

def validate_plan(raw, request):
    return service().validate_plan(raw, request)

def private_directory(path):
    return service().private_directory(path)

def execute_plan(plan, request):
    return service().execute_plan(plan, request)

def public_command(tool, arguments):
    return service().public_command(tool, arguments)

def assert_operation_context(plan):
    return service().assert_operation_context(plan)

def execution_command(plan):
    return service().execution_command(plan)

def is_management_command(name):
    return service().is_management_command(name)

def launch(args):
    return service().launch(args)
