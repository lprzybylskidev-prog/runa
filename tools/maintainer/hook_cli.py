"""Owner composition injects immutable history policy into shared staged checks."""

import sys

from tools.application.git_hook import main
from tools.maintainer.hook_policy import immutable_history

if __name__ == "__main__":
    main(sys.argv[1:], history=immutable_history, module="tools.maintainer.hook_cli")
