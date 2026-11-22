-- Pandoc filter for the handbook PDF.

-- Relative links only work inside the repository, so the PDF points them to GitHub.
local repository_url = 'https://github.com/MeditationKarlsruhe/kmc-eversports/blob/main/'
local supported_targets = { '^https?://', '^mailto:', '^#' }

local function is_supported(target)
  for _, pattern in ipairs(supported_targets) do
    if target:match(pattern) then
      return true
    end
  end
  return false
end

function Link(link)
  local path = link.target:match('^%.%./(.+)$')
  if path then
    link.target = repository_url .. path
  elseif not is_supported(link.target) then
    error('Unsupported link target in the handbook: ' .. link.target)
  end
  return link
end

-- Pandoc would give all columns the same width; every handbook table has two columns.
function Table(table)
  table.colspecs = { { pandoc.AlignDefault, 0.3 }, { pandoc.AlignDefault, 0.7 } }
  return table
end

-- The Markdown names no plugin version, so that it cannot get out of date.
function Pandoc(doc)
  local title = doc.blocks[1]
  if not title or title.t ~= 'Header' then
    error('The handbook must start with its title.')
  end
  local version = pandoc.utils.stringify(doc.meta['plugin-version'])
  doc.blocks:insert(2, pandoc.Para('Plugin-Version ' .. version))
  return doc
end
