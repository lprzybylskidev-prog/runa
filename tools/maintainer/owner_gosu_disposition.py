"""Explicit owner acceptance; never selected by customer scanner execution."""
import gosu_disposition
from runtime import ROOT


def apply(image, report_path, archive_path):
    marker = ROOT / '.git/runa-bootstrap.json'
    if marker.exists() or marker.is_symlink() or not (ROOT / 'tools/maintainer').is_dir():
        raise ValueError('Owner pending-upstream acceptance is unavailable in customer installations.')
    return gosu_disposition.apply(image, report_path, archive_path,
        policy_path=ROOT / 'tools/maintainer/security/postgres-gosu-pending-upstream.json',
        allowed_statuses=('accepted_risk_pending_upstream',))
