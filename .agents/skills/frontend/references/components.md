# Components reference

The authoritative delivered contract is [the public components guide](../../../../docs/runa/handbook/development/frontend/components.md). Load it only for the current task. Maintain platform capability contracts there and owner-specific implementation decisions in the active owner roadmap. Customer application patterns belong in their local App guidance; extracting application controls never authorizes protected-source edits.

For public screen/layout composition and full replacement, use [frontend
adoption](../../../../docs/runa/handbook/development/frontend/customization.md).
New platform views require usable defaults and a documented public replacement
contract in the same point. Keep presentation separated from reusable controllers;
export supported contracts through facades rather than private imports.
