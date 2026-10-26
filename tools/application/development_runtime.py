"""Development Compose policy composed over the core command builder."""

from dataclasses import dataclass

from tools.core.context import ComposeContext, ExecutionContext, ExecutionLocation, WorkspaceContext


def development_compose(workspace: WorkspaceContext) -> ComposeContext:
    root = workspace.root
    return ComposeContext(
        workspace,
        (root / "compose.yaml", root / ".devcontainer/compose.resources.yaml"),
        root / ".env.dev.local",
    )


@dataclass(frozen=True)
class DevelopmentRuntime:
    context: ExecutionContext

    def app_command(self, arguments: tuple[str, ...]) -> tuple[str, ...]:
        if self.context.location is ExecutionLocation.CONTAINER:
            return arguments
        return development_compose(self.context.tools).command("exec", "-T", "app", *arguments)
