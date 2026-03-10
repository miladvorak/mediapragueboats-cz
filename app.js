/* Prague Boats Media Hub — Application Logic */

(function () {
  "use strict";

  // ============================================================
  // Lazy Loading Images with Intersection Observer
  // ============================================================
  var lazyImages = document.querySelectorAll("img[data-src]");

  function loadImage(img) {
    img.src = img.getAttribute("data-src");
    img.removeAttribute("data-src");
    img.addEventListener("load", function () {
      img.classList.add("loaded");
    });
    if (img.complete) {
      img.classList.add("loaded");
    }
  }

  if ("IntersectionObserver" in window) {
    var imgObserver = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            loadImage(entry.target);
            imgObserver.unobserve(entry.target);
          }
        });
      },
      { rootMargin: "400px 0px" }
    );

    lazyImages.forEach(function (img) {
      imgObserver.observe(img);
    });
  } else {
    lazyImages.forEach(function (img) {
      loadImage(img);
    });
  }

  // ============================================================
  // Animate Elements on Scroll
  // ============================================================
  var animateElements = document.querySelectorAll(
    ".media-card, .event-item, .video-item, .news-item"
  );

  if ("IntersectionObserver" in window) {
    var staggerIndex = 0;
    var animObserver = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            // Small stagger delay based on position within viewport
            var rect = entry.boundingClientRect;
            var delay = Math.min(rect.top / 3000, 0.3);
            entry.target.style.animationDelay = delay.toFixed(2) + "s";
            entry.target.classList.add("visible");
            animObserver.unobserve(entry.target);
          }
        });
      },
      { rootMargin: "0px 0px -40px 0px", threshold: 0.05 }
    );

    animateElements.forEach(function (el) {
      animObserver.observe(el);
    });
  } else {
    animateElements.forEach(function (el) {
      el.classList.add("visible");
    });
  }

  // ============================================================
  // Copy to Clipboard
  // ============================================================
  var toast = document.getElementById("toast");
  var toastTimeout;

  function showToast(message) {
    if (toast) {
      var span = toast.querySelector("span");
      if (span && message) {
        span.textContent = message;
      }
      toast.classList.add("visible");
      clearTimeout(toastTimeout);
      toastTimeout = setTimeout(function () {
        toast.classList.remove("visible");
      }, 2000);
    }
  }

  document.addEventListener("click", function (e) {
    var btn = e.target.closest(".copy-btn");
    if (!btn) return;

    e.preventDefault();
    e.stopPropagation();

    var text = btn.getAttribute("data-clipboard-text");
    if (!text) return;

    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(
        function () {
          btn.classList.add("copied");
          setTimeout(function () {
            btn.classList.remove("copied");
          }, 1500);
          showToast("Link copied to clipboard");
        },
        function () {
          fallbackCopy(text, btn);
        }
      );
    } else {
      fallbackCopy(text, btn);
    }
  });

  function fallbackCopy(text, btn) {
    var textArea = document.createElement("textarea");
    textArea.value = text;
    textArea.style.position = "fixed";
    textArea.style.left = "-9999px";
    document.body.appendChild(textArea);
    textArea.select();
    try {
      document.execCommand("copy");
      btn.classList.add("copied");
      setTimeout(function () {
        btn.classList.remove("copied");
      }, 1500);
      showToast("Link copied to clipboard");
    } catch (err) {
      showToast("Failed to copy");
    }
    document.body.removeChild(textArea);
  }

  // ============================================================
  // Sidebar Navigation — Smooth Scroll
  // ============================================================
  var sidebar = document.getElementById("sidebar");
  var sidebarNav = document.getElementById("sidebarNav");
  var allNavLinks = sidebarNav
    ? sidebarNav.querySelectorAll("[data-target]")
    : [];

  function setActiveNav(targetId) {
    allNavLinks.forEach(function (link) {
      link.classList.remove("active");
    });

    // Also handle nav-group-links
    var groupLinks = document.querySelectorAll(".nav-group-link");
    groupLinks.forEach(function (link) {
      link.classList.remove("active");
    });

    // Find and activate the matching link
    var matchLink = sidebarNav
      ? sidebarNav.querySelector('[data-target="' + targetId + '"]')
      : null;
    if (matchLink) {
      matchLink.classList.add("active");
      // Open parent details if needed
      var parentDetails = matchLink.closest("details.nav-group");
      if (parentDetails) {
        parentDetails.open = true;
      }
    }

    // Check nav-group-links too
    var matchGroupLink = document.querySelector(
      '.nav-group-link[data-target="' + targetId + '"]'
    );
    if (matchGroupLink) {
      matchGroupLink.classList.add("active");
    }
  }

  // Handle nav link clicks
  function handleNavClick(e) {
    var link = e.target.closest("[data-target]");
    if (!link) return;

    e.preventDefault();

    var targetId = link.getAttribute("data-target");
    var targetEl = document.getElementById(targetId);

    if (targetEl) {
      targetEl.scrollIntoView({ behavior: "smooth", block: "start" });
      setActiveNav(targetId);

      // Close mobile sidebar
      if (window.innerWidth <= 768) {
        closeSidebar();
      }
    }
  }

  if (sidebarNav) {
    sidebarNav.addEventListener("click", handleNavClick);
  }

  // Handle nav-group-link clicks
  var navGroupLinks = document.querySelectorAll(".nav-group-link");
  navGroupLinks.forEach(function (link) {
    link.addEventListener("click", function (e) {
      e.preventDefault();
      var targetId = link.getAttribute("data-target");
      var targetEl = document.getElementById(targetId);
      if (targetEl) {
        targetEl.scrollIntoView({ behavior: "smooth", block: "start" });
        setActiveNav(targetId);
        if (window.innerWidth <= 768) {
          closeSidebar();
        }
      }
    });
  });

  // ============================================================
  // Mobile Hamburger Menu
  // ============================================================
  var hamburger = document.getElementById("hamburger");
  var sidebarOverlay = document.getElementById("sidebarOverlay");

  function openSidebar() {
    if (sidebar) sidebar.classList.add("open");
    if (hamburger) hamburger.classList.add("active");
    if (sidebarOverlay) sidebarOverlay.classList.add("visible");
    document.body.style.overflow = "hidden";
  }

  function closeSidebar() {
    if (sidebar) sidebar.classList.remove("open");
    if (hamburger) hamburger.classList.remove("active");
    if (sidebarOverlay) sidebarOverlay.classList.remove("visible");
    document.body.style.overflow = "";
  }

  if (hamburger) {
    hamburger.addEventListener("click", function () {
      if (sidebar && sidebar.classList.contains("open")) {
        closeSidebar();
      } else {
        openSidebar();
      }
    });
  }

  if (sidebarOverlay) {
    sidebarOverlay.addEventListener("click", closeSidebar);
  }

  // ============================================================
  // Active section tracking on scroll
  // ============================================================
  var sections = document.querySelectorAll(
    ".subsection[id], .content-section[id]"
  );

  if ("IntersectionObserver" in window && sections.length > 0) {
    var scrollObserver = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            setActiveNav(entry.target.id);
          }
        });
      },
      { rootMargin: "-20% 0px -70% 0px", threshold: 0 }
    );

    sections.forEach(function (section) {
      scrollObserver.observe(section);
    });
  }

  // ============================================================
  // Handle initial hash
  // ============================================================
  if (window.location.hash) {
    var hash = window.location.hash.substring(1);
    var hashTarget = document.getElementById(hash);
    if (hashTarget) {
      setTimeout(function () {
        hashTarget.scrollIntoView({ behavior: "smooth", block: "start" });
        setActiveNav(hash);
      }, 300);
    }
  }

  // ============================================================
  // Fleet Nav — Collapse / Expand Boats
  // ============================================================
  var fleetItems = document.querySelectorAll(".nav-item.has-children");

  fleetItems.forEach(function (item) {
    var boatLink = item.querySelector(".nav-link:not(.nav-sub-link)");
    if (!boatLink) return;

    boatLink.addEventListener("click", function (e) {
      // Prevent default scroll behavior — we toggle instead
      e.preventDefault();
      e.stopPropagation();

      // Toggle expanded class
      var isExpanded = item.classList.contains("expanded");
      item.classList.toggle("expanded");

      // If expanding, scroll to the boat's section in the content
      if (!isExpanded) {
        var targetId = boatLink.getAttribute("data-target");
        var targetEl = document.getElementById(targetId);
        if (targetEl) {
          targetEl.scrollIntoView({ behavior: "smooth", block: "start" });
          setActiveNav(targetId);
        }
        // Close mobile sidebar
        if (window.innerWidth <= 768) {
          closeSidebar();
        }
      }
    });
  });

  // ============================================================
  // Theme Toggle — Dark / Light
  // ============================================================
  var themeDarkBtn = document.getElementById("themeDark");
  var themeLightBtn = document.getElementById("themeLight");

  function setTheme(theme) {
    if (theme === "light") {
      document.documentElement.setAttribute("data-theme", "light");
      if (themeDarkBtn) themeDarkBtn.classList.remove("active");
      if (themeLightBtn) themeLightBtn.classList.add("active");
    } else {
      document.documentElement.removeAttribute("data-theme");
      if (themeDarkBtn) themeDarkBtn.classList.add("active");
      if (themeLightBtn) themeLightBtn.classList.remove("active");
    }
  }

  // Theme starts as dark (default), no persistence needed

  if (themeDarkBtn) {
    themeDarkBtn.addEventListener("click", function () {
      setTheme("dark");
    });
  }

  if (themeLightBtn) {
    themeLightBtn.addEventListener("click", function () {
      setTheme("light");
    });
  }
})();
