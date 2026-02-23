      </main>
    </div>

    <script>
      const nav = document.querySelector(".nav");
      const links = nav.querySelectorAll("[data-target]");

      function setActive(targetId) {
        links.forEach((link) => link.classList.remove("active"));
        const active = nav.querySelector(`[data-target="${targetId}"]`);
        if (active) {
          active.classList.add("active");
          const group = active.closest("details");
          if (group) {
            group.open = true;
          }
        }
      }

      links.forEach((link) => {
        link.addEventListener("click", (event) => {
          event.preventDefault();
          const targetId = link.getAttribute("data-target");
          const target = document.querySelector(targetId);
          if (target) {
            target.scrollIntoView({ behavior: "smooth", block: "start" });
            history.replaceState(null, "", targetId);
            setActive(targetId);
          }
        });
      });

      document.querySelectorAll("img[data-src]").forEach((img) => {
        img.src = img.getAttribute("data-src");
        img.loading = "lazy";
        img.removeAttribute("data-src");
      });

      document.querySelectorAll(".copy").forEach((btn) => {
        btn.addEventListener("click", (event) => {
          event.preventDefault();
          const text = btn.getAttribute("data-clipboard-text");
          if (!text) {
            return;
          }
          navigator.clipboard.writeText(text);
        });
      });

      if (location.hash) {
        setActive(location.hash);
      }
    </script>
  </body>
</html>
