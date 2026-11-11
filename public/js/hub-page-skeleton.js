document.addEventListener('DOMContentLoaded', function () {
    function finishHubSkeleton(skeletonId) {
        var skeleton = document.getElementById(skeletonId);
        if (!skeleton || skeleton.dataset.done === '1') {
            return;
        }

        skeleton.dataset.done = '1';
        skeleton.classList.add('is-hidden');
        skeleton.setAttribute('aria-hidden', 'true');

        window.setTimeout(function () {
            if (skeleton.parentNode) {
                skeleton.parentNode.removeChild(skeleton);
            }
        }, 220);
    }

    function initHubSkeleton(skeletonId) {
        var skeleton = document.getElementById(skeletonId);
        if (!skeleton) {
            return;
        }

        var done = false;
        function run() {
            if (done) return;
            done = true;
            window.requestAnimationFrame(function () {
                window.setTimeout(function () {
                    finishHubSkeleton(skeletonId);
                }, 260);
            });
        }

        run();
        window.addEventListener('load', run);
    }

    // Auto-init every page skeleton (list, pipeline, settings, reports, etc.)
    document.querySelectorAll('.hub-page-skeleton[id]').forEach(function (el) {
        if (el.id) {
            initHubSkeleton(el.id);
        }
    });
});
