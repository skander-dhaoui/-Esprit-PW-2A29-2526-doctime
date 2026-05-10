
</div> <!-- .main-content -->

<button id="scrollToTop" class="btn btn-teal shadow-lg position-fixed bottom-0 end-0 m-4 rounded-circle d-none align-items-center justify-content-center" style="width: 45px; height: 45px; z-index: 1000;">
    <i class="fas fa-arrow-up text-white"></i>
</button>
<button id="scrollToBottom" class="btn btn-teal shadow-lg position-fixed bottom-0 end-0 m-4 rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; z-index: 1000; margin-bottom: 70px !important;">
    <i class="fas fa-arrow-down text-white"></i>
</button>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const btnBottom = document.getElementById('scrollToBottom');
const btnTop = document.getElementById('scrollToTop');

if(btnBottom) btnBottom.onclick = () => window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
if(btnTop) btnTop.onclick = () => window.scrollTo({ top: 0, behavior: 'smooth' });

window.addEventListener('scroll', () => {
    if (window.scrollY > 300) {
        btnTop.classList.remove('d-none');
        btnTop.classList.add('d-flex');
    } else {
        btnTop.classList.add('d-none');
        btnTop.classList.remove('d-flex');
    }
});
</script>
</body>
</html>
