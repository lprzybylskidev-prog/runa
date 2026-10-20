"""TEI capability validation and explicit CUDA-to-CPU fallback."""
import math
import sys
from dataclasses import dataclass
from transport import Http, RequestError


@dataclass(frozen=True)
class Capabilities:
    provider: str
    model: str
    revision: str
    dimension: int
    max_tokens: int
    batch_size: int
    runtime: str


class Embeddings:
    def __init__(self, config, device='auto'):
        self.config = config
        self.device = device
        self.capabilities = Capabilities(
            'tei', config['model_id'], config['model_revision'], config['dimension'],
            config['max_input_tokens'], config['batch_size'], config['runtime_version'],
        )
        self.cpu = Http(config['cpu_url'])
        self.gpu = Http(config['cuda_url'])
        self.client = self.cpu
        self.validated = set()
        if device != 'cpu':
            try:
                self.validate(self.gpu)
                self.client = self.gpu
            except RequestError:
                print('CUDA embeddings unavailable; using CPU fallback.', file=sys.stderr)
        self.validate(self.client)

    def validate(self, client):
        if client.base in self.validated:
            return
        info = client.request('GET', '/info')
        expected = {'model_id': self.capabilities.model, 'model_sha': self.capabilities.revision,
                    'version': self.capabilities.runtime, 'model_dtype': self.config['dtype']}
        if any(info.get(k) != v for k, v in expected.items()):
            raise ValueError('Embedding service model/revision/runtime/dtype mismatch; refusing mixed embedding spaces.')
        if info.get('model_type') != {'embedding': {'pooling': self.config['pooling']}}:
            raise ValueError('Embedding pooling/model type differs from the knowledge contract.')
        if info['max_input_length'] != self.capabilities.max_tokens or info['auto_truncate']:
            raise ValueError('Embedding service input limits/truncation differ from the knowledge contract.')
        if info['max_client_batch_size'] < self.capabilities.batch_size:
            raise ValueError('Embedding service batch limit is too small.')
        self.validated.add(client.base)

    def request(self, path, body):
        try:
            return self.client.request('POST', path, body)
        except RequestError as error:
            if self.client is self.cpu or error.status not in (None, 408, 429, 500, 502, 503, 504):
                raise
            print('CUDA embedding request failed (possible GPU OOM); switching to CPU fallback.', file=sys.stderr)
            self.validate(self.cpu)
            self.client = self.cpu
            return self.client.request('POST', path, body)

    def counts(self, texts):
        result = []
        for start in range(0, len(texts), self.capabilities.batch_size):
            batch = texts[start:start + self.capabilities.batch_size]
            tokens = self.request('/tokenize', {'inputs': batch, 'add_special_tokens': True})
            if len(tokens) != len(batch):
                raise ValueError('Tokenizer response count mismatch.')
            result.extend(len(row) for row in tokens)
        return result

    def embed(self, texts):
        if any(n > self.capabilities.max_tokens for n in self.counts(texts)):
            raise ValueError('Embedding input exceeds the model token budget; truncation is forbidden.')
        result = []
        for start in range(0, len(texts), self.capabilities.batch_size):
            batch = texts[start:start + self.capabilities.batch_size]
            vectors = self.request('/embed', {'inputs': batch, 'truncate': False, 'normalize': self.config['normalize']})
            if len(vectors) != len(batch):
                raise ValueError('Embedding response count mismatch.')
            for vector in vectors:
                if len(vector) != self.capabilities.dimension or not all(isinstance(x, (float, int)) and math.isfinite(x) for x in vector):
                    raise ValueError('Invalid embedding dimension or non-finite value.')
                if not any(vector):
                    raise ValueError('Embedding service returned a zero vector.')
            result.extend(vectors)
        return result
