"""Private deployment composition with a nonexecuting process boundary."""

from contextlib import contextmanager
from unittest.mock import Mock

from tools.application.deployment_context import OperationContext
from tools.application.deployment_runtime import DeploymentRuntime
from tools.application.deployment_service import DeploymentService
from tools.application.deployment_source import SourceAcquisition
from tools.application.processes import ApplicationProcesses
from tools.application.production_runtime import ProductionRuntime
from tools.application.testing.support.process import RecordingRunner
from tools.core.context import ExecutionLocation, WorkspaceContext


def deployment(root):
    actual = OperationContext(
        WorkspaceContext(root),
        ApplicationProcesses(RecordingRunner(), {"RUNA_HOST_WORKER": "1"}),
        ExecutionLocation.HOST,
    )
    context = Mock(wraps=actual)
    context.workspace = actual.workspace
    context.state = actual.state
    context.processes = actual.processes
    context.location = actual.location
    source = SourceAcquisition(context)
    runtime = DeploymentRuntime(context, source, ProductionRuntime(context))
    return DeploymentService(context, source, runtime)


@contextmanager
def bind_deployment(case, root):
    previous = case.deployment
    case.deployment = deployment(root)
    try:
        yield
    finally:
        case.deployment = previous
