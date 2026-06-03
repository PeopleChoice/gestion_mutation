@extends('layouts.app')
@section('title', 'Demandes d\'annulation')

@section('content')
<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Lot</th>
                    <th>Projet</th>
                    <th>Propriétaire actuel</th>
                    <th>Demandé par</th>
                    <th>Motif</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($annulations as $annulation)
                <tr>
                    <td><strong>{{ $annulation->mutation->parcelle->numero_lot }}</strong></td>
                    <td>{{ $annulation->mutation->parcelle->projet->nom }}</td>
                    <td>{{ $annulation->mutation->nouveauProprietaire?->nom_complet ?? '-' }}</td>
                    <td>{{ $annulation->demandeur->name }}</td>
                    <td>{{ Str::limit($annulation->motif, 50) }}</td>
                    <td>{{ $annulation->created_at->format('d/m/Y H:i') }}</td>
                    <td>
                        <form action="{{ route('annulations.traiter', $annulation) }}" method="POST" class="d-inline" data-confirm="Approuver cette annulation ?<br><small class='text-muted'>Le terrain sera remis à l'ancien propriétaire.</small>" data-confirm-type="success" data-confirm-label="Approuver">
                            @csrf
                            <input type="hidden" name="action" value="approuver">
                            <button type="submit" class="btn btn-sm btn-success">
                                <i class="bi bi-check-lg"></i> Approuver
                            </button>
                        </form>
                        <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#modalRejet{{ $annulation->id }}">
                            <i class="bi bi-x-lg"></i> Rejeter
                        </button>

                        <!-- Modal rejet -->
                        <div class="modal fade" id="modalRejet{{ $annulation->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="{{ route('annulations.traiter', $annulation) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="action" value="rejeter">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Rejeter l'annulation</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label">Motif du rejet</label>
                                                <textarea name="motif_rejet" class="form-control" rows="3" required></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="submit" class="btn btn-danger">Confirmer le rejet</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">Aucune demande d'annulation en attente</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{ $annulations->links() }}
@endsection
