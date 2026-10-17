"""Qdrant REST storage; collection identity and interrupted writes are explicit."""
from corpus import identity
from transport import Http, RequestError

MANIFEST = identity('index-manifest')


class Store:
    def __init__(self, config):
        self.http = Http(config['qdrant_url'])
        self.path = '/collections/' + config['collection']
        self.config = config

    def exists(self):
        try:
            result = self.http.request('GET', self.path)['result']
        except RequestError as error:
            if error.status == 404:
                return False
            raise
        vectors = result['config']['params']['vectors']
        if vectors.get('size') != self.config['dimension'] or vectors.get('distance') != 'Cosine':
            raise ValueError('Qdrant vector configuration mismatch; explicit rebuild required.')
        return True

    def create(self):
        self.http.request('PUT', self.path, {'vectors': {'size': self.config['dimension'], 'distance': 'Cosine'}})

    def manifest(self):
        points = self.http.request('POST', self.path + '/points', {'ids': [MANIFEST], 'with_payload': True, 'with_vector': False})['result']
        return points[0]['payload'] if points else None

    def write_manifest(self, payload):
        self.upsert([{'id': MANIFEST, 'vector': [1.] + [0.] * (self.config['dimension'] - 1),
                      'payload': {**payload, 'kind': 'index_metadata'}}])

    def upsert(self, points):
        self.http.request('PUT', self.path + '/points?wait=true', {'points': points})

    def payload(self, point_id, payload):
        self.http.request('PUT', self.path + '/points/payload?wait=true', {'points': [point_id], 'payload': payload})

    def delete(self, ids):
        for start in range(0, len(ids), 128):
            self.http.request('POST', self.path + '/points/delete?wait=true', {'points': ids[start:start + 128]})

    def all_points(self):
        result = {}
        offset = None
        while True:
            body = {'limit': 256, 'with_payload': True, 'with_vector': False}
            if offset is not None:
                body['offset'] = offset
            response = self.http.request('POST', self.path + '/points/scroll', body)['result']
            for point in response['points']:
                if point['id'] != MANIFEST:
                    result[point['id']] = point['payload']
            offset = response.get('next_page_offset')
            if offset is None:
                return result
