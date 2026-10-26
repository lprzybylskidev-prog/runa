"""Compatibility calls; new consumers compose DeploymentService explicitly."""

from pathlib import Path

from tools.application.deployment_context import OperationContext
from tools.application.deployment_context import read_env as read_env
from tools.application.deployment_context import stamp as stamp
from tools.application.deployment_runtime import DeploymentRuntime
from tools.application.deployment_service import DeploymentService
from tools.application.deployment_source import SourceAcquisition
from tools.application.production_runtime import ProductionRuntime
from tools.core.context import WorkspaceContext


def service(root=None, processes=None, location=None):
    workspace = WorkspaceContext(
        Path(root) if root is not None else Path(__file__).resolve().parents[2]
    )
    context = OperationContext.create(workspace, processes=processes, location=location)
    source = SourceAcquisition(context)
    production = ProductionRuntime(context)
    runtime = DeploymentRuntime(context, source=source, production=production)
    return DeploymentService(context, source=source, runtime=runtime)


def resolve(*args, root=None, **kwargs):
    return service(root).source.resolve(*args, **kwargs)


def tree_hash(*args, root=None, **kwargs):
    return service(root).source.tree_hash(*args, **kwargs)


def checkout(*args, root=None, **kwargs):
    return service(root).source.checkout(*args, **kwargs)


def assert_delivery(*args, root=None, **kwargs):
    return service(root).source.assert_delivery(*args, **kwargs)


def command(*args, root=None, **kwargs):
    return service(root).runtime.command(*args, **kwargs)


def dc(*args, root=None, **kwargs):
    return service(root).runtime.dc(*args, **kwargs)


def artisan(*args, root=None, **kwargs):
    return service(root).runtime.artisan(*args, **kwargs)


def http_maintenance(*args, root=None, **kwargs):
    return service(root).runtime.http_maintenance(*args, **kwargs)


def pause_http(*args, root=None, **kwargs):
    return service(root).runtime.pause_http(*args, **kwargs)


def resume_http(*args, root=None, **kwargs):
    return service(root).runtime.resume_http(*args, **kwargs)


def validate(*args, root=None, **kwargs):
    return service(root).runtime.validate(*args, **kwargs)


def resolved(*args, root=None, **kwargs):
    return service(root).runtime.resolved(*args, **kwargs)


def platform_data(*args, root=None, **kwargs):
    return service(root).runtime.platform_data(*args, **kwargs)


def images(*args, root=None, **kwargs):
    return service(root).runtime.images(*args, **kwargs)


def health(*args, root=None, **kwargs):
    return service(root).runtime.health(*args, **kwargs)


def isolation(*args, root=None, **kwargs):
    return service(root).runtime.isolation(*args, **kwargs)


def build_images(*args, root=None, **kwargs):
    return service(root).runtime.build_images(*args, **kwargs)


def maintenance_active(*args, root=None, **kwargs):
    return service(root).runtime.maintenance_active(*args, **kwargs)


def active_release(*args, root=None, **kwargs):
    return service(root).active_release(*args, **kwargs)


def set_tag(*args, root=None, **kwargs):
    return service(root).set_tag(*args, **kwargs)


def deploy(*args, root=None, **kwargs):
    return service(root).deploy(*args, **kwargs)


def deploy_revision(*args, root=None, **kwargs):
    return service(root).deploy_revision(*args, **kwargs)


def rollback(*args, root=None, **kwargs):
    return service(root).rollback(*args, **kwargs)


def main(*args, root=None, **kwargs):
    return service(root).main(*args, **kwargs)


def lock(*args, root=None, **kwargs):
    return service(root).context.lock(*args, **kwargs)


def read_state(*args, root=None, **kwargs):
    return service(root).context.read_state(*args, **kwargs)


def environment(*args, root=None, **kwargs):
    return service(root).context.environment(*args, **kwargs)


def atomic(*args, root=None, **kwargs):
    return service(root).context.atomic(*args, **kwargs)
