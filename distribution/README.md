# Owner-controlled distribution tools

This directory is maintained only in the Runa source/maintenance checkout.
It is excluded from installed customer dev/prod source, build inputs and images.
The owner runs these tools personally or deliberately hands the reviewed standalone
bootstrap.py to a customer separately; the customer does not receive this directory
as part of the ready-to-use environment. No automated entitlement/license service
is implemented. Public Git history remains public until repository access changes.

bootstrap.py is the self-contained initial source acquisition/installation launcher.
The future standalone updater belongs here when its roadmap point is implemented.
Do not create a placeholder updater or copy these tools into bin or an installation.
Internal system actions needed for operation/recovery remain in the platform;
they are not an installed owner distribution command surface.

maintenance contains owner-only command registration/state/release helpers and
the maintainer hook transport. It is omitted from customer registration even if
accidentally restored alongside an installation receipt. The Runa maintainer
checkout retains its own workflow, with explicit local runa.maintenance=true
and canonical Runa fetch/push identity before project-state publishing; customer deployment evidence never commits or
pushes to the Runa repository.
