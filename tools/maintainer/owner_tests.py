"""Compatibility API for callers moving to the private composition root."""

from tools.maintainer.test_cli import main


def execute(suite, args):
    return main([suite, *args])
