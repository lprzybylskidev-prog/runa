# Owner-controlled distribution tools

This directory is maintained only in the Runa source/maintenance checkout.
It is excluded from installed customer dev/prod source, build inputs and images.
The owner runs these tools personally or deliberately hands the reviewed standalone
bootstrap.py to a customer separately; the customer does not receive this directory
as part of the ready-to-use environment. No automated entitlement/license service
is implemented. Public Git history remains public until repository access changes.

bootstrap.py acquires a verified Runa release for first development. initialize.py selects a complete customer repository revision for production; teammate clones use the delivered runa:dev:initialize workflow.
update.py with update_contracts.py, update_runtime.py, update_customization.py, update_execution.py, update_modules.py, module-plan.php and bootstrap.py provides development-only read-only preflight/plan, explicit apply and independently verified journaled resume. Production receives the complete tested customer revision through deployment. Keep this reviewed standard-library tool package together outside the customer installation; it needs no maintenance checkout. Never copy these tools into bin or installed source.
Internal system actions needed for operation/recovery remain in the platform;
they are not an installed owner distribution command surface.

Owner maintenance/quality/release entrypoints live under tools/maintainer and are omitted from customer registration/delivery, even if accidentally restored alongside an installation receipt. The Runa maintainer
checkout retains its own workflow, with explicit local runa.maintenance=true
and canonical Runa fetch/push identity before project-state publishing; customer deployment evidence never commits or
pushes to the Runa repository.
