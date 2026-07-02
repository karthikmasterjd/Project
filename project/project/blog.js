/**
 * Sethu Jewellery - Client side Blog logic
 * Fetches dynamic blog posts and renders interactive cards grid.
 */
document.addEventListener('DOMContentLoaded', () => {
    let allBlogs = [];
    const blogGrid = document.getElementById('blogGrid');
    const blogSearch = document.getElementById('blogSearch');
    
    // Modal Reader elements
    const blogModal = document.getElementById('blogModal');
    const blogModalClose = document.getElementById('blogModalClose');
    const modalBlogImage = document.getElementById('modalBlogImage');
    const modalBlogTitle = document.getElementById('modalBlogTitle');
    const modalBlogAuthor = document.getElementById('modalBlogAuthor');
    const modalBlogDate = document.getElementById('modalBlogDate');
    const modalBlogContent = document.getElementById('modalBlogContent');

    // 1. Fetch live jewellery rates for top bar ticker
    fetch('api/site-data.php')
        .then(res => res.json())
        .then(data => {
            if (data.rates && data.settings) {
                // Populate rates ticker
                const ratesLabel = document.getElementById('topBarRatesLabel');
                if (ratesLabel) {
                    const g22 = data.rates.gold22 ? `₹${data.rates.gold22.toLocaleString('en-IN')}/g` : '';
                    const g24 = data.rates.gold24 ? `₹${data.rates.gold24.toLocaleString('en-IN')}/g` : '';
                    const silver = data.rates.silver ? `₹${data.rates.silver.toLocaleString('en-IN')}/g` : '';
                    ratesLabel.innerHTML = `Today's Rates (Karaikudi): <i class="fa-solid fa-gem" style="color: var(--gold-light); margin-left: 10px;"></i> Gold 22K: <span style="color:white; font-weight:700;">${g22}</span> | Gold 24K: <span style="color:white; font-weight:700;">${g24}</span> | Silver: <span style="color:white; font-weight:700;">${silver}</span>`;
                }
            }
        })
        .catch(err => console.error('Failed to load rates in blog:', err));

    // 2. Fetch all published blogs from API
    fetch('api/blog-data.php')
        .then(res => {
            if (!res.ok) throw new Error('API server returned error status.');
            return res.json();
        })
        .then(blogs => {
            allBlogs = blogs;
            renderBlogs(allBlogs);
        })
        .catch(err => {
            console.error('Failed to load blogs:', err);
            blogGrid.innerHTML = `
                <div style="grid-column: span 3; text-align: center; padding: 60px 0;">
                    <i class="fa-solid fa-triangle-exclamation" style="font-size: 40px; color: var(--gold-dark); margin-bottom: 15px;"></i>
                    <p style="color: var(--text-muted); max-width: 400px; margin: 0 auto 15px;">We were unable to load the blog articles at this time. Please try refreshing the page.</p>
                </div>
            `;
        });

    // 3. Render blogs grid helper
    function renderBlogs(articles) {
        if (articles.length === 0) {
            blogGrid.innerHTML = `
                <div style="grid-column: span 3; text-align: center; padding: 60px 0;">
                    <i class="fa-solid fa-folder-open" style="font-size: 40px; color: var(--text-muted); margin-bottom: 15px;"></i>
                    <p style="color: var(--text-muted);">No blog articles found matching your criteria.</p>
                </div>
            `;
            return;
        }

        blogGrid.innerHTML = '';
        articles.forEach(art => {
            // Calculate reading time (avg 200 words per minute)
            const wordCount = art.content.split(/\s+/).length;
            const readTime = Math.max(1, Math.ceil(wordCount / 200));

            // Format date
            const dateObj = new Date(art.createdAt);
            const formattedDate = dateObj.toLocaleDateString('en-IN', {
                day: 'numeric',
                month: 'short',
                year: 'numeric'
            });

            // Create blog card
            const card = document.createElement('div');
            card.className = 'blog-card';
            card.innerHTML = `
                <div class="blog-img-area">
                    <img src="${art.image}" alt="${art.title}">
                </div>
                <div class="blog-card-body">
                    <div class="blog-meta">
                        <span><i class="fa-solid fa-user"></i> ${art.author}</span>
                        <span><i class="fa-solid fa-calendar-days"></i> ${formattedDate}</span>
                        <span><i class="fa-solid fa-clock"></i> ${readTime} min read</span>
                    </div>
                    <h3>${art.title}</h3>
                    <p class="blog-excerpt">${art.content.substring(0, 140)}...</p>
                    <div class="blog-readmore">Read Full Article <i class="fa-solid fa-arrow-right-long"></i></div>
                </div>
            `;

            // Open reader modal on click
            card.addEventListener('click', () => {
                openBlogModal(art, formattedDate);
            });

            blogGrid.appendChild(card);
        });
    }

    // 4. Open blog modal helper
    function openBlogModal(art, formattedDate) {
        modalBlogImage.src = art.image;
        modalBlogTitle.textContent = art.title;
        modalBlogAuthor.textContent = art.author;
        modalBlogDate.textContent = formattedDate;
        modalBlogContent.textContent = art.content;

        blogModal.classList.add('active');
        document.body.style.overflow = 'hidden'; // Disable background scroll
    }

    // 5. Close blog modal helper
    function closeBlogModal() {
        blogModal.classList.remove('active');
        document.body.style.overflow = ''; // Re-enable scroll
    }

    blogModalClose.addEventListener('click', closeBlogModal);
    blogModal.addEventListener('click', (e) => {
        if (e.target === blogModal) {
            closeBlogModal();
        }
    });

    // 6. Live Text Search Filter handler
    if (blogSearch) {
        blogSearch.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase().trim();
            const filtered = allBlogs.filter(art => {
                return art.title.toLowerCase().includes(query) || 
                       art.author.toLowerCase().includes(query) || 
                       art.content.toLowerCase().includes(query);
            });
            renderBlogs(filtered);
        });
    }
});
