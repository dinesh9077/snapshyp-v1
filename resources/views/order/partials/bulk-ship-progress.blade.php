{{-- Live bulk-ship progress: counters, progress bar and per-order results, polled from
     order.bulk-shipment.status. "Minimize" keeps the run going in a floating card; the batch id
     is remembered in localStorage so a page reload resumes it (minimized). --}}
<style>
    .bp-stats { display: flex; flex-wrap: wrap; gap: 6px; }
    .bp-stat {
        border: 1px solid #e3e7ef; background: #f7f9fc; border-radius: 999px; padding: 4px 12px;
        font-size: 13px; font-weight: 600; color: #4a5763; cursor: pointer;
    }
    .bp-stat.is-active { background: #1c1f3a; border-color: #1c1f3a; color: #fff; }
    .bp-stat.ok i { color: #1f9d55; } .bp-stat.fail i { color: #d64545; } .bp-stat.skip i { color: #8a8577; }
    .bp-stat.is-active i { color: inherit; }
    .bp-list { border: 1px solid #e3e7ef; border-radius: 8px; max-height: 340px; overflow-y: auto; }
    .bp-row { display: flex; align-items: flex-start; gap: 10px; padding: 8px 12px; border-bottom: 1px solid #f0f2f6; font-size: 13px; }
    .bp-row:last-child { border-bottom: 0; }
    .bp-row .bp-ico { width: 18px; text-align: center; }
    .bp-row .bp-num { font-weight: 600; min-width: 70px; }
    .bp-row .bp-msg { color: #4a5763; flex: 1; min-width: 0; overflow-wrap: anywhere; }
    .bp-row.st-success .bp-ico { color: #1f9d55; }
    .bp-row.st-failed .bp-ico, .bp-row.st-failed .bp-msg { color: #d64545; }
    .bp-row.st-skipped { opacity: .7; }
    .bp-progress { height: 10px; border-radius: 6px; background: #edf0f5; }
    #bulkProgressMini {
        position: fixed; right: 16px; bottom: 16px; z-index: 1060; width: 300px; max-width: calc(100vw - 32px);
        background: #fff; border: 1px solid #e3e7ef; border-radius: 12px;
        box-shadow: 0 10px 30px rgba(20, 20, 40, .18); padding: 12px 14px; cursor: pointer;
    }
    #bulkProgressMini .bp-progress { height: 6px; }
</style>

<div class="modal fade" id="bulkProgressModal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title d-flex align-items-center" style="gap: 8px;">
                    <span id="bpIcon"></span>
                    <span id="bpTitle">Shipping orders…</span>
                </h5>
                <button type="button" class="close" id="bpMinimizeX" title="Minimize — keeps running in the background">
                    <i class="mdi mdi-close"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between align-items-end mb-1">
                    <div class="small text-muted" id="bpSubtitle">Waiting for the queue to pick up your orders…</div>
                    <div class="font-weight-bold" id="bpFraction">0 / 0</div>
                </div>
                <div class="progress bp-progress mb-3">
                    <div class="progress-bar bg-success" id="bpBarOk" style="width:0%"></div>
                    <div class="progress-bar bg-danger" id="bpBarFail" style="width:0%"></div>
                </div>

                <div class="bp-stats mb-3">
                    <button type="button" class="bp-stat is-active" data-filter="all"><span id="bpAll">0</span> All</button>
                    <button type="button" class="bp-stat" data-filter="processing"><i class="mdi mdi-timer-sand"></i> <span id="bpProc">0</span> Processing</button>
                    <button type="button" class="bp-stat ok" data-filter="success"><i class="mdi mdi-check-circle"></i> <span id="bpOk">0</span> Success</button>
                    <button type="button" class="bp-stat fail" data-filter="failed"><i class="mdi mdi-close-circle"></i> <span id="bpFail">0</span> Failed</button>
                    <button type="button" class="bp-stat skip" data-filter="skipped"><i class="mdi mdi-skip-next-circle"></i> <span id="bpSkip">0</span> Skipped</button>
                </div>

                <div class="alert alert-warning small py-2 d-none" id="bpStalled">
                    <i class="mdi mdi-alert mr-1"></i>
                    No order has started yet — the queue worker (cron) may not be running. Your orders stay queued
                    and will be processed as soon as it runs.
                </div>

                <div id="bpList" class="bp-list">
                    <div class="text-muted small text-center py-3">Loading…</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" id="bpMinimize">
                    <i class="mdi mdi-window-minimize mr-1"></i> Minimize
                </button>
                <button type="button" class="btn btn-outline-danger d-none" id="bpRetry">
                    <i class="mdi mdi-refresh mr-1"></i> Retry failed (<span id="bpRetryCount">0</span>)
                </button>
                <button type="button" class="new-submit-btn d-none" id="bpDone">
                    <i class="mdi mdi-check mr-1"></i> Done
                </button>
            </div>
        </div>
    </div>
</div>

<div id="bulkProgressMini" class="d-none" role="button" title="Show bulk shipping progress">
    <div class="d-flex align-items-center mb-1" style="gap: 8px;">
        <span id="bpMiniIcon"></span>
        <strong id="bpMiniTitle">Shipping orders…</strong>
        <span class="ml-auto font-weight-bold" id="bpMiniFraction">0 / 0</span>
    </div>
    <div class="progress bp-progress">
        <div class="progress-bar bg-success" id="bpMiniOk" style="width:0%"></div>
        <div class="progress-bar bg-danger" id="bpMiniFail" style="width:0%"></div>
    </div>
    <div class="small text-muted mt-1" id="bpMiniCounts"></div>
</div>

<script>
    window.bulkShipProgress = (function() {
        const statusBase = @json(url('order/bulk-shipment/status'));
        const retryBase = @json(url('order/bulk-shipment/retry'));
        const orderBase = @json(url('order/details'));
        const STORE_KEY = 'snapshyp.bulkShipBatch';
        const store = {
            get() { try { return localStorage.getItem(STORE_KEY); } catch (e) { return null; } },
            set(v) { try { localStorage.setItem(STORE_KEY, v); } catch (e) {} },
            clear() { try { localStorage.removeItem(STORE_KEY); } catch (e) {} },
        };
        const SPIN = '<i class="mdi mdi-loading mdi-spin text-primary"></i>';
        const esc = s => String(s ?? '').replace(/[&<>"']/g, ch => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch]));
        const bp = { id: null, timer: null, data: null, filter: 'all', errors: 0, resumed: false, startedAt: 0, lastDone: -1 };

        function start(batchId, opts = {}) {
            if (!batchId) return;
            clearTimeout(bp.timer);
            Object.assign(bp, { id: batchId, data: null, errors: 0, resumed: !!opts.resumed, startedAt: Date.now(), lastDone: -1 });
            store.set(batchId);
            setFilter('all');
            $('#bpList').html('<div class="text-muted small text-center py-3">Loading…</div>');
            $('#bpRetry, #bpDone').addClass('d-none');
            $('#bpStalled').addClass('d-none');
            setHeader(false, 0);
            if (opts.minimized) minimize(); else show();
            // Queued orders now render as "Processing" (no Ship Now button): show that at once
            if (!opts.resumed) reloadOrderTables();
            tick();
        }

        function show() {
            $('#bulkProgressMini').addClass('d-none');
            $('#bulkProgressModal').modal('show');
        }

        function minimize() {
            $('#bulkProgressModal').modal('hide');
            $('#bulkProgressMini').removeClass('d-none');
        }

        function tick() {
            $.ajax({ url: statusBase + '/' + encodeURIComponent(bp.id), dataType: 'json', cache: false })
                .done(function(b) {
                    bp.errors = 0;
                    bp.data = b;
                    render(b);
                    if (b.status === 'done') finish(b);
                    else bp.timer = setTimeout(tick, interval(b));
                })
                .fail(function(xhr) {
                    // A remembered run that no longer exists (or is not ours): forget it quietly
                    if (xhr.status === 404) { close(false); return; }
                    bp.errors++;
                    bp.timer = setTimeout(tick, Math.min(10000, 2000 * bp.errors));
                });
        }

        // Fast polling for small runs and the first 30s; long runs settle to every 3s
        function interval(b) {
            return ((b.total || 0) <= 20 || Date.now() - bp.startedAt < 30000) ? 1500 : 3000;
        }

        function counts(b) {
            const total = b.total || 0, ok = b.success || 0, fail = b.failed || 0, skip = b.skipped || 0;
            return { total, ok, fail, skip, done: ok + fail, proc: Math.max(0, total - ok - fail) };
        }

        function setHeader(finished, failed) {
            const icon = finished
                ? '<i class="mdi ' + (failed ? 'mdi-alert text-warning' : 'mdi-check-circle text-success') + '"></i>'
                : SPIN;
            const title = finished ? 'Bulk shipping complete' : 'Shipping orders…';
            $('#bpIcon, #bpMiniIcon').html(icon);
            $('#bpTitle, #bpMiniTitle').text(title);
        }

        function render(b) {
            const c = counts(b);
            const pct = n => (c.total ? (n / c.total * 100) : 0).toFixed(1) + '%';
            const frac = c.done + ' / ' + c.total;

            $('#bpFraction, #bpMiniFraction').text(frac);
            $('#bpBarOk, #bpMiniOk').css('width', pct(c.ok));
            $('#bpBarFail, #bpMiniFail').css('width', pct(c.fail));
            $('#bpAll').text(c.total + c.skip);
            $('#bpProc').text(c.proc);
            $('#bpOk').text(c.ok);
            $('#bpFail').text(c.fail);
            $('#bpSkip').text(c.skip);
            $('#bpMiniCounts').html(
                '<i class="mdi mdi-check-circle text-success"></i> ' + c.ok + ' shipped · ' +
                '<i class="mdi mdi-close-circle text-danger"></i> ' + c.fail + ' failed' +
                (c.proc ? ' · <i class="mdi mdi-timer-sand"></i> ' + c.proc + ' in queue' : ''));

            let sub;
            if (b.status === 'done') {
                sub = c.ok + ' shipped, ' + c.fail + ' failed' + (c.skip ? ', ' + c.skip + ' skipped' : '') + '.';
            } else if (!b.started) {
                sub = 'Waiting for the queue to pick up your orders…';
            } else {
                sub = 'Booking with the courier — you can minimize this and keep working.';
            }
            $('#bpSubtitle').text(sub);
            $('#bpStalled').toggleClass('d-none', !b.stalled);

            renderList(b);

            // Refresh the order list whenever more orders finish, so their rows move out of "New"
            if (c.done !== bp.lastDone) {
                if (bp.lastDone !== -1) reloadOrderTables();
                bp.lastDone = c.done;
            }
        }

        function renderList(b) {
            const $list = $('#bpList');
            const f = bp.filter;
            const rows = (b.items || []).filter(it => f === 'all' ||
                (f === 'processing' ? (it.status === 'queued' || it.status === 'processing') : it.status === f));
            if (!rows.length) {
                $list.html('<div class="text-muted small text-center py-3">Nothing here.</div>');
                return;
            }
            const scroll = $list.scrollTop();
            $list.html(rows.map(function(it) {
                const num = '<a href="' + orderBase + '/' + it.order_id + '" target="_blank">#' + esc(String(it.order_id).padStart(5, '0')) + '</a>';
                let ico, msg;
                switch (it.status) {
                    case 'queued': ico = '<i class="mdi mdi-clock-outline text-muted"></i>'; msg = 'Queued'; break;
                    case 'processing': ico = SPIN; msg = 'Booking with the courier…'; break;
                    case 'success': ico = '<i class="mdi mdi-check-circle"></i>';
                        msg = 'Shipped' + (it.awb ? ' · AWB <b>' + esc(it.awb) + '</b>' : '') + (it.courier ? ' · ' + esc(it.courier) : ''); break;
                    case 'failed': ico = '<i class="mdi mdi-close-circle"></i>'; msg = esc(it.error || 'Shipment failed'); break;
                    default: ico = '<i class="mdi mdi-skip-next-circle"></i>'; msg = 'Skipped — ' + esc(it.error || 'not eligible');
                }
                return '<div class="bp-row st-' + esc(it.status) + '"><span class="bp-ico">' + ico + '</span>' +
                    '<span class="bp-num">' + num + '</span><span class="bp-msg">' + msg + '</span></div>';
            }).join(''));
            $list.scrollTop(scroll);
        }

        function finish(b) {
            clearTimeout(bp.timer);
            const c = counts(b);
            setHeader(true, c.fail);
            const retryable = (b.items || []).filter(it => it.status === 'failed').length;
            $('#bpRetryCount').text(retryable);
            $('#bpRetry').toggleClass('d-none', !retryable);
            $('#bpDone').removeClass('d-none');
            $('#bpStalled').addClass('d-none');
            reloadOrderTables();
            // Finished: a later reload should not bring this run back
            store.clear();
        }

        function close(reload) {
            clearTimeout(bp.timer);
            store.clear();
            bp.id = null;
            $('#bulkProgressModal').modal('hide');
            $('#bulkProgressMini').addClass('d-none');
            if (reload) reloadOrderTables();
        }

        function setFilter(f) {
            bp.filter = f;
            $('#bulkProgressModal .bp-stat').each(function() {
                $(this).toggleClass('is-active', $(this).data('filter') === f);
            });
            if (bp.data) renderList(bp.data);
        }

        function reloadOrderTables() {
            if ($.fn.dataTable) {
                $.fn.dataTable.tables({ api: true }).ajax.reload(null, false);
            }
        }

        $(document).on('click', '#bulkProgressModal .bp-stat', function() { setFilter($(this).data('filter')); });
        $(document).on('click', '#bpMinimize', minimize);
        $(document).on('click', '#bpMinimizeX', function() {
            // Once finished the X closes; while running it minimizes
            if (bp.data && bp.data.status === 'done') close(true); else minimize();
        });
        $(document).on('click', '#bpDone', function() { close(true); });
        $(document).on('click', '#bulkProgressMini', show);
        $(document).on('click', '#bpRetry', function() {
            const $btn = $(this).prop('disabled', true);
            $.ajax({
                url: retryBase + '/' + encodeURIComponent(bp.id), type: 'POST', dataType: 'json',
                data: { _token: '{{ csrf_token() }}' },
            }).done(function(res) {
                $btn.prop('disabled', false);
                if (res.batch_id) start(res.batch_id); else toastrMsg('error', res.msg || 'No failed orders can be retried.');
            }).fail(function() {
                $btn.prop('disabled', false);
                toastrMsg('error', 'Could not retry the failed orders. Please try again.');
            });
        });

        // Resume a run that was still going when the page was reloaded
        $(function() {
            const saved = store.get();
            if (saved) start(saved, { minimized: true, resumed: true });
        });

        return { start };
    })();
</script>
