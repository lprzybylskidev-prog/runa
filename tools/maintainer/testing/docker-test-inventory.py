#!/usr/bin/env python3
"""Read-only Docker inventory for final scoped cleanup; never prints container environments."""
import json
import subprocess
from pathlib import Path
root=Path(__file__).resolve().parents[3]

def read(args):
    return subprocess.run(['docker',*args],check=True,capture_output=True,text=True,stdin=subprocess.DEVNULL).stdout

def lines(args):
    return [json.loads(line) for line in read(args).splitlines() if line]

containers=lines(['ps','-a','--format','json'])
volumes=lines(['volume','ls','--format','json'])
networks=lines(['network','ls','--format','json'])
images=lines(['image','ls','--no-trunc','--digests','--format','json'])
# Only selected inspect fields are emitted; env/passwords are never included.
scoped=[]
for row in containers:
    if 'runa' not in row.get('Names',''):
        continue
    raw=json.loads(read(['inspect',row['ID']]))[0]
    scoped.append({'id':raw['Id'],'name':raw['Name'],'image':raw['Config']['Image'],'image_id':raw['Image'],'state':raw['State']['Status'],'project':raw['Config'].get('Labels',{}).get('com.docker.compose.project'),'mounts':[{'type':m['Type'],'name':m.get('Name'),'destination':m['Destination']} for m in raw['Mounts']]})
data={'containers':scoped,'images':[r for r in images if any(x in r.get('Repository','') for x in ('runa','postgres','aquasec/trivy'))], 'networks':[r for r in networks if 'runa' in r.get('Name','')], 'volumes':[r for r in volumes if 'runa' in r.get('Name','')]}
print(json.dumps(data,indent=2))
