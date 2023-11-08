const ChangeName = () => {
  const [fname, setFname] = React.useState(plFname)
  const [lname, setLname] = React.useState(plLname)
  const [loading, setLoading] = React.useState('')
  const [errorMessage, setErrorMessage] = React.useState()
  const [successMessage, setSuccessMessage] = React.useState()

  const onSubmit = async e => {
    e.preventDefault()

    setSuccessMessage(undefined)
    setErrorMessage(undefined)
    setLoading(true)
    setFname('')
    setLname('')

    const response = await fetch('rest/api.php?q=changename', {
      method: 'POST',
      body: JSON.stringify({ user_id: plUserId, fname, lname }),
    })

    // ignore responseString in this json. It's always "OK".
    const json = await response.json()
    setLoading(false)

    switch (response.status) {
      case 200:
        setSuccessMessage('Name address successfully changed.')
        break

      case 403:
        setErrorMessage(
          'You are not logged in as an administrator. Please reload the page and log in again.',
        )
        break
    }
  }

  return (
    <form {...{ onSubmit }}>
      <TextField label='First Name' value={fname} changeHandler={e => setFname(e.target.value)} />
      <TextField label='Last Name' value={lname} changeHandler={e => setLname(e.target.value)} />

      <div id='spacious-form-button-spinner'>
        {loading ? <img src='spinner.gif' /> : <button type='submit'>Save</button>}
      </div>

      {successMessage && <div className='confirmation'>{successMessage}</div>}
      {errorMessage && <div className='error'>{errorMessage}</div>}
    </form>
  )
}

ReactDOM.render(React.createElement(ChangeName), document.querySelector('#change-name'))
