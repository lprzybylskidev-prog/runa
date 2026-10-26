"""Compatibility diagnostics; new callers construct Diagnostics explicitly."""

import os

from tools.application.configuration_reader import ConfigurationReader
from tools.application.diagnostics import Diagnostics
from tools.application.processes import ApplicationProcesses
from tools.application.runtime import ROOT
from tools.core.context import WorkspaceContext
from tools.core.process import SubprocessRunner


def yaml(path):
    return ConfigurationReader.for_workspace(ROOT).yaml(path)


def service():
    return Diagnostics(
        WorkspaceContext(ROOT), ApplicationProcesses(SubprocessRunner(), dict(os.environ))
    )


def repository():
    return service().repository()


def maintenance():
    return service().maintenance()


def docs():
    return service().docs()


def tooling():
    return service().tooling()


def development():
    return service().development()


def boost():
    return service().boost()
