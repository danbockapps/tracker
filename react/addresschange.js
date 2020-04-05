const AddressInput = ({ label, name, value, changeHandler }) => {
  return (
    <label>
      {label}
      <input name={name} type='text' value={value} onChange={changeHandler} />
    </label>
  )
}

const AddressChange = () => {
  const [address, setAddress] = React.useReducer(
    (state, newState) => ({ ...state, ...newState }),
    {
      address1: '',
      address2: '',
      city: '',
      state: '',
      zip: '',
      phone: '',
    }
  )

  const [loading, setLoading] = React.useState(false)

  React.useEffect(() => {
    fetch('rest/api.php?q=addresschange')
      .then(response => response.json())
      .then(data => setAddress(data.data))
  }, [])

  const handleInputChange = e => setAddress({ [e.target.name]: e.target.value })

  const handleSubmit = e => {
    setLoading(true)
    fetch('rest/api.php?q=addresschange', {
      method: 'POST',
      body: JSON.stringify(address),
    })
      .then(response => response.json())
      .then(data => {
        setLoading(false)
        if (data.responseString === 'OK')
          alert('Your address has been updated.')
        else alert('An error occurred while updating your address.')
      })
      .catch(e => alert('An error occurred during address update.'))
    e.preventDefault()
  }

  /*
  For better performance, we could convert this JSX to JS using
  https://babeljs.io/repl or a preprocessor.
  */

  return (
    <form onSubmit={handleSubmit}>
      <AddressInput
        label='Address Line 1'
        name='address1'
        value={address.address1}
        changeHandler={handleInputChange}
      />
      <AddressInput
        label='Address Line 2'
        name='address2'
        value={address.address2}
        changeHandler={handleInputChange}
      />
      <AddressInput
        label='City'
        name='city'
        value={address.city}
        changeHandler={handleInputChange}
      />
      <AddressInput
        label='State'
        name='state'
        value={address.state}
        changeHandler={handleInputChange}
      />
      <AddressInput
        label='Zip'
        name='zip'
        value={address.zip}
        changeHandler={handleInputChange}
      />
      <AddressInput
        label='Phone'
        name='phone'
        value={address.phone}
        changeHandler={handleInputChange}
      />

      <div id='address-change-button-spinner'>
        {loading ? (
          <img src='spinner.gif' />
        ) : (
          <button type='submit'>Save</button>
        )}
      </div>
    </form>
  )
}

const domContainer = document.querySelector('#address-change')
ReactDOM.render(React.createElement(AddressChange), domContainer)
